<?php

namespace Tests\Feature\Campaign;

use App\Enums\Campaign\SeckillActivityStatus;
use App\Enums\Campaign\SeckillSessionStatus;
use App\Enums\Mall\FulfillmentType;
use App\Models\Campaign\SeckillActivity;
use App\Models\Campaign\SeckillRecord;
use App\Models\Campaign\SeckillSession;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Mall\Seckill\RedisSeckillStockCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SeckillApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Redis::connection()->flushdb();
    }

    public function test_activity_list_returns_published_sessions(): void
    {
        [, , $session] = $this->fixture();

        $this->getJson('/api/campaign/seckill/activities')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', '测试秒杀')
            ->assertJsonPath('0.sessions.0.session_id', $session->getKey())
            ->assertJsonPath('0.sessions.0.remaining', 5)
            ->assertJsonPath('0.sessions.0.status.value', SeckillSessionStatus::Running->value);
    }

    public function test_session_detail_returns_server_time_and_user_limit(): void
    {
        [, $user, $session] = $this->fixture();

        Sanctum::actingAs($user);

        $this->getJson("/api/campaign/seckill/sessions/{$session->getKey()}")
            ->assertOk()
            ->assertJsonPath('session_id', $session->getKey())
            ->assertJsonPath('remaining', 5)
            ->assertJsonPath('user_limit_remaining', 2)
            ->assertJsonStructure(['server_time', 'countdown_seconds', 'seckill_price']);
    }

    public function test_order_creates_seckill_order(): void
    {
        [, $user, $session] = $this->fixture();

        Sanctum::actingAs($user);

        $this->postJson("/api/campaign/seckill/sessions/{$session->getKey()}/orders", [
            'qty' => 1,
            'fulfillment_type' => FulfillmentType::Virtual->value,
        ])->assertCreated();

        $order = Order::query()->firstOrFail();

        $this->assertEquals(99.0, $order->total_amount);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->getKey(),
            'source' => 'seckill',
            'status' => 'pending',
        ]);

        $this->assertSame(1, SeckillRecord::query()->count());
        $this->assertSame(1, $session->fresh()->sold);
    }

    public function test_sold_out_session_returns_business_error(): void
    {
        [, $user, $session] = $this->fixture(stock: 1);

        Sanctum::actingAs($user);

        $this->postJson("/api/campaign/seckill/sessions/{$session->getKey()}/orders", [
            'qty' => 2,
            'fulfillment_type' => FulfillmentType::Virtual->value,
        ])->assertStatus(400);
    }

    /**
     * @return array{0: Tenant, 1: User, 2: SeckillSession}
     */
    private function fixture(int $stock = 5): array
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();

        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'fulfillment_type' => [FulfillmentType::Virtual->value],
        ]);

        $sku = Sku::factory()->create([
            'product_id' => $product->getKey(),
            'price' => 199,
            'stock' => 100,
        ]);

        $activity = SeckillActivity::create([
            'tenant_id' => $tenant->id,
            'name' => '测试秒杀',
            'status' => SeckillActivityStatus::Published,
        ]);

        $session = SeckillSession::create([
            'tenant_id' => $tenant->id,
            'activity_id' => $activity->getKey(),
            'name' => '10:00 场',
            'product_id' => $product->getKey(),
            'sku_id' => $sku->getKey(),
            'seckill_price' => 99,
            'stock' => $stock,
            'sold' => 0,
            'limit_per_user' => 2,
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'status' => SeckillSessionStatus::Running,
        ]);

        service(RedisSeckillStockCounter::class)->prepare($session);

        return [$tenant, $user, $session];
    }
}
