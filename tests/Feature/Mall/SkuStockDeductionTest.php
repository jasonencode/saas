<?php

namespace Tests\Feature\Mall;

use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Exceptions\Mall\InsufficientStockException;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Mall\DTOs\OrderItemDto;
use App\Services\Mall\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * 下单库存扣减验证
 *
 * 覆盖：条件扣减防并发超卖、扣减失败整单回滚、购买数量入参校验。
 */
class SkuStockDeductionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Product $product;

    private Sku $sku;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();

        // 下单减库存 + 虚拟履约，便于直达扣减逻辑
        $this->product = Product::factory()->for($this->tenant, 'tenant')->create([
            'deduct_stock_type' => DeductStockType::Ordered,
            'fulfillment_type' => [FulfillmentType::Virtual->value],
        ]);

        $this->sku = Sku::factory()->create([
            'product_id' => $this->product->id,
            'price' => '88.00',
            'stock' => 10,
        ]);
    }

    // ─── Sku::deductStock 条件扣减 ───────────────────────────────

    public function test_deduct_stock_succeeds_when_stock_sufficient(): void
    {
        $this->sku->deductStock(4);

        $this->assertSame(6, $this->sku->fresh()->stock);
    }

    public function test_deduct_stock_allows_exact_stock(): void
    {
        $this->sku->deductStock(10);

        $this->assertSame(0, $this->sku->fresh()->stock);
    }

    public function test_deduct_stock_throws_and_keeps_stock_when_insufficient(): void
    {
        $exception = $this->captureRuntimeException(fn () => $this->sku->deductStock(11));

        $this->assertNotNull($exception, '库存不足时必须抛出异常以回滚整单');
        $this->assertStringContainsString('库存不足', $exception->getMessage());
        $this->assertSame(10, $this->sku->fresh()->stock, '扣减失败不得改动库存');
    }

    public function test_deduct_stock_rejects_oversell_when_stock_taken_after_precheck(): void
    {
        // 模拟并发：前置校验时读到库存 10，另一请求抢先扣空
        $stale = Sku::find($this->sku->id);
        $this->assertSame(10, $stale->stock);

        Sku::whereKey($this->sku->id)->update(['stock' => 0]);

        $exception = $this->captureRuntimeException(fn () => $stale->deductStock(10));

        $this->assertNotNull($exception, '条件扣减必须拦截超卖');
        $this->assertSame(0, $stale->fresh()->stock, '库存不得被扣成负数');
    }

    // ─── 付款减库存 ──────────────────────────────────────────────

    public function test_paid_stock_type_deducts_stock_on_payment(): void
    {
        Notification::fake();

        $this->product->update(['deduct_stock_type' => DeductStockType::Paid]);

        $order = service(OrderService::class)
            ->createOrders($this->user, [OrderItemDto::forPreview($this->sku, 3)], FulfillmentType::Virtual)
            ->first();

        $this->assertSame(10, $this->sku->fresh()->stock, '付款减库存商品下单时不应扣库存');

        service(OrderService::class)->pay($order, $this->user);

        $this->assertSame(7, $this->sku->fresh()->stock, '支付成功时必须扣减库存');
        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
    }

    public function test_payment_rolls_back_when_paid_stock_insufficient(): void
    {
        Notification::fake();

        $this->product->update(['deduct_stock_type' => DeductStockType::Paid]);

        $order = service(OrderService::class)
            ->createOrders($this->user, [OrderItemDto::forPreview($this->sku, 3)], FulfillmentType::Virtual)
            ->first();

        // 模拟支付前库存被其他订单抢空
        $this->sku->update(['stock' => 0]);

        try {
            service(OrderService::class)->pay($order, $this->user);
            $this->fail('库存不足时支付必须失败');
        } catch (InsufficientStockException $e) {
            $this->assertStringContainsString('库存不足', $e->getMessage());
        }

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status, '扣库存失败必须回滚订单状态');
    }

    // ─── 事务回滚 ────────────────────────────────────────────────

    public function test_order_rolls_back_when_any_item_stock_insufficient(): void
    {
        $emptySku = Sku::factory()->create([
            'product_id' => $this->product->id,
            'price' => '10.00',
            'stock' => 0,
        ]);

        // 走 forPreview 绕过前置校验，强制进入事务内的扣减环节
        $items = [
            OrderItemDto::forPreview($this->sku, 1),
            OrderItemDto::forPreview($emptySku, 1),
        ];

        $exception = $this->captureRuntimeException(
            fn () => service(OrderService::class)->createOrders($this->user, $items, FulfillmentType::Virtual)
        );

        $this->assertNotNull($exception);
        $this->assertStringContainsString('库存不足', $exception->getMessage());
        $this->assertSame(0, Order::count(), '整单必须回滚，不得落库');
        $this->assertSame(10, $this->sku->fresh()->stock, '同单内已成功的扣减必须一并回滚');
    }

    // ─── POST /api/mall/orders 数量校验 ──────────────────────────

    public function test_create_order_deducts_stock(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders', $this->payload(['qty' => 3]))
            ->assertCreated();

        $this->assertSame(7, $this->sku->fresh()->stock);
        $this->assertSame(1, Order::count());
    }

    public function test_create_order_accepts_numeric_string_qty(): void
    {
        Sanctum::actingAs($this->user);

        // 兼容旧客户端字符串传参：integer 规则接受纯数字字符串
        $this->postJson('/api/mall/orders', $this->payload(['qty' => '3']))
            ->assertCreated();

        $this->assertSame(7, $this->sku->fresh()->stock);
    }

    public function test_create_order_rejects_decimal_qty(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders', $this->payload(['qty' => 1.5]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');

        $this->assertSame(10, $this->sku->fresh()->stock);
        $this->assertSame(0, Order::count());
    }

    public function test_create_order_rejects_qty_above_max(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders', $this->payload(['qty' => 1000]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');

        $this->assertSame(0, Order::count());
    }

    public function test_create_order_rejects_qty_below_min(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders', $this->payload(['qty' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');

        $this->assertSame(0, Order::count());
    }

    public function test_preview_rejects_qty_above_max(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders/preview', $this->payload(['qty' => 1000]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qty');
    }

    public function test_preview_accepts_numeric_string_qty(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders/preview', $this->payload(['qty' => '2']))
            ->assertOk();
    }

    // ─── 辅助方法 ────────────────────────────────────────────────

    /**
     * 捕获 RuntimeException，未抛出时返回 null
     */
    private function captureRuntimeException(callable $callback): ?RuntimeException
    {
        try {
            $callback();
        } catch (RuntimeException $e) {
            return $e;
        }

        return null;
    }

    /**
     * 立即购买下单/预览请求体
     *
     * @param  array<string, mixed>  $override  覆盖字段
     *
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return array_merge([
            'orderable_type' => 'sku',
            'orderable_id' => $this->sku->id,
            'qty' => 1,
            'fulfillment_type' => FulfillmentType::Virtual->value,
        ], $override);
    }
}
