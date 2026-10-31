<?php

namespace Tests\Feature\Mall;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Mall\DeductStockType;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Finance\PaymentOrder;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Product;
use App\Models\Mall\Refund;
use App\Models\Mall\Sku;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Mall\DTOs\RefundData;
use App\Services\Mall\DTOs\RefundItemData;
use App\Services\Mall\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

/**
 * 退款服务补充动作：改金额、重置申请、删除、重试支付退款。
 */
class RefundOperationsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_update_refund_amount_updates_goods_freight_and_total(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Delivered, freight: '10.00');
        $refund = $this->makeRefund($order, $item, RefundType::ReturnRefund);

        service(RefundService::class)->updateRefundAmount($refund, $this->user, 80, 5);

        $refund->refresh();
        $this->assertSame('80.00', $refund->goods_amount);
        $this->assertSame('5.00', $refund->freight_amount);
        $this->assertSame('85.00', $refund->total);
        $this->assertDatabaseHas('event_logs', [
            'event' => 'refund.amount_updated',
            'subject_id' => $refund->id,
        ]);
    }

    public function test_update_refund_amount_rejects_amount_over_order_paid(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Delivered, freight: '10.00');
        $refund = $this->makeRefund($order, $item, RefundType::ReturnRefund);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('退款总额超出订单实付金额');

        service(RefundService::class)->updateRefundAmount($refund, $this->user, 100, 10);
    }

    public function test_update_refund_amount_rejects_negative_amount(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Delivered, freight: '10.00');
        $refund = $this->makeRefund($order, $item, RefundType::ReturnRefund);

        $this->expectExceptionMessage('退款金额不能小于 0');

        service(RefundService::class)->updateRefundAmount($refund, $this->user, -1, 0);
    }

    public function test_reapply_rejected_refund_resets_to_pending(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Delivered);
        $refund = $this->makeRefund($order, $item, RefundType::ReturnRefund);
        $admin = Administrator::factory()->create();

        service(RefundService::class)->rejectRefund($refund, $admin, '不符合退款条件');
        $this->assertSame(RefundStatus::Rejected, $refund->fresh()->status);

        service(RefundService::class)->reApplyRefund($refund, $admin, '重新审核');

        $refund->refresh();
        $this->assertSame(RefundStatus::Pending, $refund->status);
        $this->assertNull($refund->approved_by);
        $this->assertNull($refund->approved_at);
        $this->assertNull($refund->approval_remark);
        $this->assertNull($refund->refund_at);
        $this->assertDatabaseHas('event_logs', [
            'event' => 'refund.reapplied',
            'subject_id' => $refund->id,
        ]);
    }

    public function test_reapply_blocks_when_another_active_refund_exists(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Delivered);
        $first = $this->makeRefund($order, $item, RefundType::ReturnRefund);
        $admin = Administrator::factory()->create();

        service(RefundService::class)->rejectRefund($first, $admin, '不符合退款条件');

        // 被拒绝后数量释放，允许再次申请
        $second = $this->makeRefund($order->fresh(), $item->fresh(), RefundType::ReturnRefund);

        $this->expectExceptionMessage('该订单已有处理中的退款申请');

        service(RefundService::class)->reApplyRefund($first, $admin);
    }

    public function test_destroy_refund_soft_deletes_closed_refund(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Delivered);
        $refund = $this->makeRefund($order, $item, RefundType::ReturnRefund);
        $admin = Administrator::factory()->create();

        service(RefundService::class)->rejectRefund($refund, $admin, '不符合退款条件');

        service(RefundService::class)->destroyRefund($refund, $admin);

        $this->assertSoftDeleted('refunds', ['id' => $refund->id]);
        $this->assertDatabaseHas('event_logs', [
            'event' => 'refund.destroyed',
            'subject_id' => $refund->id,
        ]);
    }

    public function test_destroy_refund_rejects_pending_refund(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Delivered);
        $refund = $this->makeRefund($order, $item, RefundType::ReturnRefund);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('当前退款单状态不可删除');

        service(RefundService::class)->destroyRefund($refund, $this->user);
    }

    public function test_re_refund_completes_when_payment_refund_already_completed(): void
    {
        [$order, $item, $sku] = $this->makeOrder(OrderStatus::Completed, FulfillmentType::Virtual);

        $sku->deductStock(1);
        $this->ensurePaidPayment($order);

        $refund = $this->makeRefund($order, $item, RefundType::OnlyRefund);
        service(RefundService::class)->processRefund($refund, $this->user);

        $paymentRefund = $refund->paymentRefunds()->latest('id')->firstOrFail();
        $paymentRefund->update([
            'status' => PaymentRefundStatus::Completed,
            'refunded_at' => now(),
        ]);

        // 模拟售后单此前被误标记失败
        $refund->update(['status' => RefundStatus::Failed]);

        service(RefundService::class)->reRefund($refund, $this->user);

        $this->assertSame(RefundStatus::Completed, $refund->fresh()->status);
        $this->assertNotNull($refund->fresh()->refund_at);
        $this->assertSame(OrderStatus::Refunded, $order->fresh()->status);
        $this->assertSame(10, $sku->fresh()->stock);
        $this->assertDatabaseHas('event_logs', [
            'event' => 'refund.retried',
            'subject_id' => $refund->id,
        ]);
    }

    public function test_re_refund_rejects_payment_refund_still_processing(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Completed, FulfillmentType::Virtual);

        $this->ensurePaidPayment($order);

        $refund = $this->makeRefund($order, $item, RefundType::OnlyRefund);
        service(RefundService::class)->processRefund($refund, $this->user);

        $this->expectExceptionMessage('支付退款单正在处理中，请勿重复发起');

        service(RefundService::class)->reRefund($refund->fresh(), $this->user);
    }

    public function test_apply_rejects_duplicate_order_items(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Delivered);

        $data = RefundData::make(
            type: RefundType::ReturnRefund,
            reason: RefundReason::NotWant,
            items: [
                RefundItemData::make($item->id, 1),
                RefundItemData::make($item->id, 1),
            ],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('退款商品重复');

        service(RefundService::class)->createRefund($order, $this->user, $data);
    }

    public function test_apply_rejects_empty_items(): void
    {
        [$order] = $this->makeOrder(OrderStatus::Delivered);

        $data = RefundData::make(
            type: RefundType::ReturnRefund,
            reason: RefundReason::NotWant,
            items: [],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('请至少选择一个退款商品');

        service(RefundService::class)->createRefund($order, $this->user, $data);
    }

    public function test_apply_rejects_quantity_over_remaining_after_partial_refund(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Delivered, qty: 2);

        $first = $this->makeRefund($order, $item, RefundType::ReturnRefund, 1);
        $first->update(['status' => RefundStatus::Completed]);

        $data = RefundData::make(
            type: RefundType::ReturnRefund,
            reason: RefundReason::NotWant,
            items: [RefundItemData::make($item->id, 2)],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('可退数量为 1');

        service(RefundService::class)->createRefund($order->fresh(), $this->user, $data);
    }

    public function test_order_detail_exposes_refundable_quantity(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Delivered, qty: 2);

        $refund = $this->makeRefund($order, $item, RefundType::ReturnRefund, 1);
        $refund->update(['status' => RefundStatus::Completed]);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/mall/orders/'.$order->no)
            ->assertOk()
            ->assertJsonPath('items.0.refunded_qty', 1)
            ->assertJsonPath('items.0.max_refund_qty', 1);
    }

    public function test_order_cannot_apply_refund_when_all_items_refunded(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Delivered);

        $refund = $this->makeRefund($order, $item, RefundType::ReturnRefund);
        $refund->update(['status' => RefundStatus::Completed]);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/mall/orders/'.$order->no)
            ->assertOk()
            ->assertJsonPath('after_sales.can_apply', false)
            ->assertJsonPath('after_sales.can_apply_types', []);
    }

    /**
     * 创建订单与订单项
     *
     * @return array{0: Order, 1: OrderItem, 2: Sku}
     */
    private function makeOrder(
        OrderStatus $status,
        FulfillmentType $fulfillmentType = FulfillmentType::Mail,
        string $freight = '0.00',
        int $qty = 1,
    ): array {
        $product = Product::factory()->for($this->tenant, 'tenant')->create([
            'deduct_stock_type' => DeductStockType::Paid,
            'fulfillment_type' => [$fulfillmentType->value],
        ]);

        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'price' => '88.00',
            'stock' => 10,
        ]);

        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => $status,
            'fulfillment_type' => $fulfillmentType,
            'amount' => bcmul('88.00', (string) $qty, 2),
            'freight' => $freight,
            'coupon_discount' => '0.00',
            'paid_at' => now(),
        ]);

        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'orderable_type' => $sku->getMorphClass(),
            'orderable_id' => $sku->id,
            'orderable_name' => $product->name,
            'qty' => $qty,
            'price' => '88.00',
            'coupon_discount' => 0,
        ]);

        return [$order->fresh(), $item->fresh(), $sku->fresh()];
    }

    private function makeRefund(Order $order, OrderItem $item, RefundType $type, ?int $qty = null): Refund
    {
        return service(RefundService::class)->createRefund(
            order: $order,
            user: $this->user,
            data: RefundData::make(
                type: $type,
                reason: RefundReason::NotWant,
                items: [RefundItemData::make($item->id, $qty ?? $item->qty)],
            ),
        );
    }

    private function ensurePaidPayment(Order $order): PaymentOrder
    {
        $payment = PaymentOrder::create([
            'tenant_id' => $order->tenant_id,
            'user_id' => $order->user_id,
            'gateway' => PaymentGateway::Balance,
            'amount' => $order->getTotalAmount(),
            'expired_at' => now()->addMinutes(30),
            'paymentable' => $order,
        ]);

        $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);

        return $payment;
    }
}
