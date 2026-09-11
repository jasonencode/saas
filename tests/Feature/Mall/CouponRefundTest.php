<?php

namespace Tests\Feature\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Campaign\Coupon;
use App\Models\Campaign\CouponUser;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Refund;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Campaign\CouponService;
use App\Services\Mall\DTOs\RefundData;
use App\Services\Mall\DTOs\RefundItemData;
use App\Services\Mall\OrderService;
use App\Services\Mall\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * 优惠券与退款闭环：取消/全额退款返还券、退款金额按券抵扣分摊、运费退还上限、超退兜底
 */
class CouponRefundTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private RefundService $refundService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->refundService = app(RefundService::class);
    }

    // ========================================
    // 测试夹具
    // ========================================

    /**
     * 创建商品 SKU（价格各不相同，模拟多商品订单）
     */
    private function makeSku(string $price): Sku
    {
        $product = Product::factory()->for($this->tenant, 'tenant')->create([
            'fulfillment_type' => [FulfillmentType::Mail->value],
        ]);

        return Sku::factory()->create([
            'product_id' => $product->id,
            'price' => $price,
            'stock' => 100,
        ]);
    }

    /**
     * 创建订单与其订单项（每项数量 1，单价即小计）
     *
     * @param  string[]  $prices  各订单项单价
     */
    private function makeOrder(
        array $prices,
        OrderStatus $status = OrderStatus::Paid,
        string $freight = '0.00'
    ): Order {
        $amount = '0.00';
        foreach ($prices as $price) {
            $amount = bcadd($amount, $price, 2);
        }

        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->getKey(),
            'amount' => $amount,
            'freight' => $freight,
            'status' => $status,
            'fulfillment_type' => FulfillmentType::Mail,
        ]);

        foreach ($prices as $price) {
            $sku = $this->makeSku($price);

            $order->items()->create([
                'orderable_type' => $sku->getMorphClass(),
                'orderable_id' => $sku->getKey(),
                'orderable_name' => '测试商品',
                'qty' => 1,
                'price' => $price,
            ]);
        }

        return $order->load('items');
    }

    /**
     * 给订单核销一张券（模拟下单占用后的订单状态）
     *
     * 分摊快照与下单一致：由 `CouponService::apportionDiscount()` 写入各订单项。
     */
    private function applyCoupon(Order $order, string $discount): CouponUser
    {
        $coupon = Coupon::factory()->fixed()->create(['tenant_id' => $this->tenant->id]);

        $couponUser = CouponUser::query()->create([
            'coupon_id' => $coupon->getKey(),
            'user_id' => $this->user->getKey(),
            'expired_at' => now()->addDay(),
            'is_used' => true,
            'used_at' => now(),
        ]);

        $order->update(['coupon_discount' => $discount]);

        foreach (service(CouponService::class)->apportionDiscount($discount, $order->items()->get()) as $itemId => $share) {
            $order->items()->whereKey($itemId)->update(['coupon_discount' => $share]);
        }

        $order->coupons()->attach($coupon->getKey(), [
            'coupon_user_id' => $couponUser->getKey(),
            'discount_amount' => $discount,
        ]);

        return $couponUser;
    }

    /**
     * 构造退款申请数据
     */
    private function refundData(
        int $orderItemId,
        RefundType $type = RefundType::OnlyRefund,
        int $qty = 1,
        float $freightAmount = 0
    ): RefundData {
        return RefundData::make(
            type: $type,
            reason: RefundReason::NotWant,
            items: [RefundItemData::make(orderItemId: $orderItemId, qty: $qty)],
            freightAmount: $freightAmount,
        );
    }

    /**
     * 申请并完成退款
     */
    private function refundAndComplete(Order $order, int $orderItemId, int $qty = 1): Refund
    {
        $refund = $this->refundService->createRefund($order, $this->user, $this->refundData($orderItemId, qty: $qty));
        $this->refundService->confirmRefund($refund, $this->user);

        return $refund->refresh();
    }

    // ========================================
    // 取消 / 全额退款返还
    // ========================================

    public function test_cancel_releases_coupon(): void
    {
        $order = $this->makeOrder(['100.00'], OrderStatus::Pending);
        $couponUser = $this->applyCoupon($order, '20.00');

        app(OrderService::class)->cancel($order, $this->user);

        $couponUser->refresh();
        $this->assertFalse((bool) $couponUser->is_used);
        $this->assertNull($couponUser->used_at);
        $this->assertSame(0.0, (float) $order->fresh()->coupon_discount);
        $this->assertDatabaseMissing('coupon_order', ['order_id' => $order->getKey()]);
    }

    public function test_full_refund_releases_coupon(): void
    {
        $order = $this->makeOrder(['100.00']);
        $couponUser = $this->applyCoupon($order, '20.00');

        $this->refundAndComplete($order, $order->items->first()->getKey());

        $couponUser->refresh();
        $this->assertFalse((bool) $couponUser->is_used);
        $this->assertNull($couponUser->used_at);
        $this->assertDatabaseMissing('coupon_order', ['order_id' => $order->getKey()]);

        // 退款已完成，订单实付口径保留为历史快照（不归零）
        $order->refresh();
        $this->assertSame('20.00', (string) $order->coupon_discount);
        $this->assertSame(80.0, $order->total_amount);
    }

    public function test_partial_refund_keeps_coupon_used(): void
    {
        $order = $this->makeOrder(['60.00', '40.00']);
        $couponUser = $this->applyCoupon($order, '20.00');

        $this->refundAndComplete($order, $order->items->first()->getKey());

        $this->assertTrue((bool) $couponUser->fresh()->is_used);
        $this->assertDatabaseHas('coupon_order', ['order_id' => $order->getKey()]);
    }

    public function test_release_is_idempotent(): void
    {
        $order = $this->makeOrder(['100.00'], OrderStatus::Pending);
        $this->applyCoupon($order, '20.00');

        $couponService = app(CouponService::class);
        $couponService->releaseFromOrder($order);
        $couponService->releaseFromOrder($order->fresh());

        $this->assertDatabaseCount('coupon_order', 0);
        $this->assertSame(0.0, (float) $order->fresh()->coupon_discount);
    }

    // ========================================
    // 退款金额分摊
    // ========================================

    public function test_refund_amount_apportioned_by_coupon(): void
    {
        // 商品 A 60 + B 40，券 20：分摊 A=12、B=8，可退 A=48、B=32
        $order = $this->makeOrder(['60.00', '40.00']);
        $this->applyCoupon($order, '20.00');

        $first = $this->refundAndComplete($order, $order->items[0]->getKey());
        $this->assertSame('48.00', (string) $first->goods_amount);

        $second = $this->refundAndComplete($order->fresh(), $order->items[1]->getKey());
        $this->assertSame('32.00', (string) $second->goods_amount);

        // 全部退完 = 实付商品金额（100 - 20）
        $this->assertSame('80.00', bcadd((string) $first->goods_amount, (string) $second->goods_amount, 2));
    }

    public function test_coupon_share_rounding_tail_lands_on_largest_item(): void
    {
        // 小计 33.33 / 33.33 / 33.34，券 10：各项分摊 3.33 后尾差 0.01 归入最大项
        $order = $this->makeOrder(['33.33', '33.33', '33.34']);
        $this->applyCoupon($order, '10.00');

        $refunded = '0.00';
        foreach ($order->items as $item) {
            $refund = $this->refundAndComplete($order->fresh(), $item->getKey());
            $refunded = bcadd($refunded, (string) $refund->goods_amount, 2);
        }

        // Σ可退商品金额 = 商品总额 − 券抵扣，尾差不丢失
        $this->assertSame('90.00', $refunded);
    }

    public function test_refund_without_coupon_keeps_original_amount(): void
    {
        // 回归：不带券订单的退款金额与现状一致
        $order = $this->makeOrder(['60.00', '40.00']);

        $refund = $this->refundAndComplete($order, $order->items->first()->getKey());

        $this->assertSame('60.00', (string) $refund->goods_amount);
    }

    // ========================================
    // 运费退还上限
    // ========================================

    public function test_freight_refund_capped_across_multiple_refunds(): void
    {
        // 未发货订单可分多笔仅退款：运费只退一次（首笔全额、后续为 0）
        $order = $this->makeOrder(['50.00', '50.00'], freight: '10.00');

        $first = $this->refundAndComplete($order, $order->items[0]->getKey());
        $this->assertSame('10.00', (string) $first->freight_amount);

        $second = $this->refundAndComplete($order->fresh(), $order->items[1]->getKey());
        $this->assertSame('0.00', (string) $second->freight_amount);

        // 两笔合计 = 实付（100 + 10）
        $this->assertSame('110.00', bcadd((string) $first->total, (string) $second->total, 2));
    }

    // ========================================
    // 超退兜底
    // ========================================

    public function test_refund_rejected_when_total_exceeds_paid(): void
    {
        // 实付 80（商品 100 − 券 20），已存在一张 60 元历史退款单
        $order = $this->makeOrder(['100.00']);
        $this->applyCoupon($order, '20.00');

        Refund::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->getKey(),
            'order_id' => $order->getKey(),
            'total' => '60.00',
            'goods_amount' => '60.00',
            'freight_amount' => '0.00',
            'status' => RefundStatus::Completed,
            'type' => RefundType::OnlyRefund,
            'reason' => RefundReason::NotWant,
            'refund_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('退款总额超出订单实付金额，无法创建退款单');

        $this->refundService->createRefund(
            $order->fresh(),
            $this->user,
            $this->refundData($order->items->first()->getKey())
        );
    }

    public function test_refund_rejected_when_all_qty_already_refunded(): void
    {
        // 已完成的退款同样占用数量，不可重复申请（原先仅统计进行中状态）
        $order = $this->makeOrder(['60.00', '40.00']);
        $orderItemId = $order->items[0]->getKey();

        $this->refundAndComplete($order, $orderItemId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/可退数量为 0/');

        $this->refundService->createRefund($order->fresh(), $this->user, $this->refundData($orderItemId));
    }

    public function test_refund_allowed_when_coupon_discount_reduces_payable(): void
    {
        // 券抵扣后实付与分摊后可退金额一致，全额退款不超退
        $order = $this->makeOrder(['100.00']);
        $this->applyCoupon($order, '20.00');
        $this->assertSame(80.0, $order->total_amount);

        $refund = $this->refundAndComplete($order, $order->items->first()->getKey());

        $this->assertSame('80.00', (string) $refund->goods_amount);
        $this->assertSame('80.00', (string) $refund->total);

        // 全额退款后券已释放；订单抵扣保留为历史快照（方案 §3.3.3 的归零仅适用于未支付取消）
        $this->assertSame('20.00', (string) $order->fresh()->coupon_discount);
        $this->assertSame(80.0, $order->fresh()->total_amount);
        $this->assertDatabaseMissing('coupon_order', ['order_id' => $order->getKey()]);
    }
}
