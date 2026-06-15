<?php

namespace Tests\Feature\Mall;

use App\Events\Mall\OrderCreated;
use App\Jobs\Mall\AutoCloseOrder;
use App\Listeners\Mall\OrderCreatedListener;
use App\Models\Mall\Order;
use App\Models\Mall\StoreConfigure;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creation_does_not_set_expiration(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $order = Order::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'amount' => '100.00',
            'freight' => '10.00',
        ]);

        $this->assertNull($order->expired_at);
    }

    public function test_order_created_listener_sets_tenant_specific_expiration_and_dispatches_auto_close(): void
    {
        Queue::fake();

        config()->set('custom.mall.order_expired_minutes', 30);

        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        StoreConfigure::create([
            'tenant_id' => $tenant->id,
            'store_name' => 'Test Store',
            'cover' => null,
            'order_expired_minutes' => 90,
        ]);

        $order = Order::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'amount' => '100.00',
            'freight' => '10.00',
        ]);

        $before = now();

        (new OrderCreatedListener)->handle(new OrderCreated($order));

        $order->refresh();

        $this->assertNotNull($order->expired_at);
        $expiresInSeconds = $order->expired_at->getTimestamp() - $before->getTimestamp();

        $this->assertGreaterThanOrEqual(5398, $expiresInSeconds);
        $this->assertLessThanOrEqual(5400, $expiresInSeconds);

        Queue::assertPushed(AutoCloseOrder::class, function (AutoCloseOrder $job) use ($order): bool {
            return $job->delay == $order->expired_at;
        });
    }
}
