<?php

namespace Tests\Feature\Mall;

use App\Enums\Mall\FulfillmentType;
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

    public function test_order_creation_falls_back_to_global_expiration(): void
    {
        config()->set('custom.mall.order_expired_minutes', 30);

        $tenant = Tenant::factory()->create();
        // users 表无 tenant_id（用户与租户经 user_tenant 关联表多对多），故不传租户
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $before = now();

        $order = Order::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'amount' => '100.00',
            'freight' => '10.00',
            'fulfillment_type' => FulfillmentType::Mail,
        ]);

        // Order::boot() 的 creating 钩子按全局 config 兜底设置过期时间，
        // 租户级StoreConfigure 覆盖发生在 OrderCreatedListener 中
        $this->assertNotNull($order->expired_at);
        $expiresInSeconds = $order->expired_at->getTimestamp() - $before->getTimestamp();

        $this->assertGreaterThanOrEqual(1798, $expiresInSeconds);
        $this->assertLessThanOrEqual(1800, $expiresInSeconds);
    }

    public function test_order_created_listener_sets_tenant_specific_expiration_and_dispatches_auto_close(): void
    {
        Queue::fake();

        config()->set('custom.mall.order_expired_minutes', 30);

        $tenant = Tenant::factory()->create();
        // users 表无 tenant_id（用户与租户经 user_tenant 关联表多对多），故不传租户
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

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
            'fulfillment_type' => FulfillmentType::Mail,
        ]);

        $before = now();

        (new OrderCreatedListener)->handle(new OrderCreated($order));

        $order->refresh();

        $this->assertNotNull($order->expired_at);
        $expiresInSeconds = $order->expired_at->getTimestamp() - $before->getTimestamp();

        $this->assertGreaterThanOrEqual(5398, $expiresInSeconds);
        $this->assertLessThanOrEqual(5400, $expiresInSeconds);

        // 延迟时间已由上方 5400±2 秒断言覆盖；delay 由队列派发时消费，
        // 任务对象本身不保留该属性，故此处只校验派发的是本订单
        // （$order 为构造时注入的 protected 属性，用反射读取避免改动生产代码可见性）
        Queue::assertPushed(AutoCloseOrder::class, function (AutoCloseOrder $job) use ($order): bool {
            $property = new \ReflectionProperty($job, 'order');

            return $property->getValue($job)->is($order);
        });
    }
}
