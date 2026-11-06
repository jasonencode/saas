<?php

namespace Tests\Feature\Mall;

use App\Enums\Campaign\SeckillActivityStatus;
use App\Enums\Campaign\SeckillRecordStatus;
use App\Enums\Campaign\SeckillSessionStatus;
use App\Enums\Mall\FulfillmentType;
use App\Models\Campaign\SeckillActivity;
use App\Models\Campaign\SeckillRecord;
use App\Models\Campaign\SeckillSession;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Mall\OrderService;
use App\Services\Mall\Seckill\RedisSeckillStockCounter;
use App\Services\Mall\Seckill\SeckillOrderService;
use App\Services\Mall\Seckill\SeckillReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Tests\TestCase;

class SeckillOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 秒杀名额使用 Redis，测试使用独立 DB（见 phpunit.xml REDIS_DB=15）
        Redis::connection()->flushdb();
    }

    public function test_seckill_order_uses_seckill_price_and_reserves_quota(): void
    {
        [, $user, $sku, $session] = $this->fixture(stock: 5, limit: 2);

        $order = service(SeckillOrderService::class)->create(
            user: $user,
            session: $session,
            qty: 2,
            fulfillmentType: FulfillmentType::Virtual,
        );

        $this->assertSame('seckill', $order->source);
        $this->assertEquals(198.0, $order->total_amount);
        $this->assertSame($session->getKey(), $order->items->first()->seckill_session_id);
        $this->assertSame(2, $session->fresh()->sold);

        $record = SeckillRecord::query()->firstOrFail();

        $this->assertSame(SeckillRecordStatus::Ordered, $record->status);
        $this->assertSame($order->getKey(), $record->order_id);
        $this->assertSame(2, $record->qty);
    }

    public function test_sold_out_session_rejects_order(): void
    {
        [, $user, , $session] = $this->fixture(stock: 1, limit: 2);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('商品已售罄');

        service(SeckillOrderService::class)->create($user, $session, 2, FulfillmentType::Virtual);
    }

    public function test_per_user_limit_is_enforced(): void
    {
        [, $user, , $session] = $this->fixture(stock: 5, limit: 1);

        service(SeckillOrderService::class)->create($user, $session, 1, FulfillmentType::Virtual);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('超出限购数量');

        service(SeckillOrderService::class)->create($user, $session, 1, FulfillmentType::Virtual);
    }

    public function test_session_not_started_rejects_order(): void
    {
        [, $user, , $session] = $this->fixture(
            stock: 5,
            limit: 1,
            status: SeckillSessionStatus::Scheduled,
            startAt: now()->addHour(),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('秒杀尚未开始');

        service(SeckillOrderService::class)->create($user, $session, 1, FulfillmentType::Virtual);
    }

    public function test_cancelling_order_restores_quota(): void
    {
        [, $user, , $session] = $this->fixture(stock: 5, limit: 2);

        $order = service(SeckillOrderService::class)->create($user, $session, 2, FulfillmentType::Virtual);
        $order->refresh();

        service(OrderService::class)->cancel($order, $user);

        $this->assertSame(0, $session->fresh()->sold);
        $this->assertSame(SeckillRecordStatus::Cancelled, SeckillRecord::query()->firstOrFail()->status);
    }

    public function test_refund_release_restores_quota(): void
    {
        [, $user, , $session] = $this->fixture(stock: 5, limit: 2);

        $order = service(SeckillOrderService::class)->create($user, $session, 2, FulfillmentType::Virtual);

        service(SeckillReservationService::class)->releaseForRefund($order);

        $this->assertSame(0, $session->fresh()->sold);
        $this->assertSame(SeckillRecordStatus::Refunded, SeckillRecord::query()->firstOrFail()->status);
    }

    /**
     * @return array{0: Tenant, 1: User, 2: Sku, 3: SeckillSession}
     */
    private function fixture(
        int $stock,
        int $limit,
        SeckillSessionStatus $status = SeckillSessionStatus::Running,
        mixed $startAt = null,
    ): array {
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

        $sessionStart = $startAt ?? now()->subMinute();

        $session = SeckillSession::create([
            'tenant_id' => $tenant->id,
            'activity_id' => $activity->getKey(),
            'name' => '10:00 场',
            'product_id' => $product->getKey(),
            'sku_id' => $sku->getKey(),
            'seckill_price' => 99,
            'stock' => $stock,
            'sold' => 0,
            'limit_per_user' => $limit,
            'start_at' => $sessionStart,
            'end_at' => $sessionStart->copy()->addHour(),
            'status' => $status,
        ]);

        service(RedisSeckillStockCounter::class)->prepare($session);

        return [$tenant, $user, $sku, $session];
    }
}
