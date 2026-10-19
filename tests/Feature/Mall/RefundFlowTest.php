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
use App\Models\Finance\PaymentRefund;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Product;
use App\Models\Mall\Refund;
use App\Models\Mall\Sku;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Finance\PaymentRefundService;
use App\Services\Finance\PaymentService;
use App\Services\Mall\DTOs\RefundData;
use App\Services\Mall\DTOs\RefundItemData;
use App\Services\Mall\OrderService;
use App\Services\Mall\RefundService;
use Database\Seeders\SystemTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * 退款链路：履约方式覆盖、资金到账后才完成售后、取消订单收款兜底。
 */
class RefundFlowTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
    }

    public function test_virtual_order_can_only_refund_and_completes_after_payment_refund(): void
    {
        [$order, $item, $sku] = $this->makeOrder(OrderStatus::Completed, FulfillmentType::Virtual, 2);

        // 模拟虚拟订单下单时已扣减库存
        $sku->deductStock(2);

        $this->assertSame(
            [RefundType::OnlyRefund],
            service(RefundService::class)->getAllowedRefundTypes($order)
        );

        $this->ensurePaidPayment($order);

        $refund = service(RefundService::class)->createRefund(
            $order,
            $this->user,
            $this->refundData($item, RefundType::OnlyRefund)
        );

        $this->assertSame(RefundStatus::Processing, $refund->status, '仅退款应自动审核通过');

        service(RefundService::class)->confirmRefund($refund, $this->user);

        $this->assertSame(RefundStatus::Processing, $refund->fresh()->status, '资金未退回前售后单不得完成');

        $paymentRefund = $refund->paymentRefunds()->latest('id')->firstOrFail();
        $this->assertSame(PaymentRefundStatus::Pending, $paymentRefund->status);

        // 模拟财务推进到处理中并确认到账
        $paymentRefund->update(['status' => PaymentRefundStatus::Processing]);
        service(PaymentRefundService::class)->complete($paymentRefund);

        $this->assertSame(RefundStatus::Completed, $refund->fresh()->status);
        $this->assertSame(OrderStatus::Refunded, $order->fresh()->status);
        $this->assertSame(10, $sku->fresh()->stock, '退款完成后应回补库存');
    }

    public function test_confirm_refund_is_idempotent_when_payment_refund_exists(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Completed, FulfillmentType::Virtual);

        $this->ensurePaidPayment($order);

        $refund = service(RefundService::class)->createRefund(
            $order,
            $this->user,
            $this->refundData($item, RefundType::OnlyRefund)
        );

        service(RefundService::class)->confirmRefund($refund, $this->user);
        service(RefundService::class)->confirmRefund($refund, $this->user);

        $this->assertSame(1, $refund->paymentRefunds()->count(), '重复提交不得生成多笔资金退款');
        $this->assertSame(RefundStatus::Processing, $refund->fresh()->status);
    }

    public function test_pending_pickup_allows_only_refund(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::PickupPending, FulfillmentType::Pickup);

        $this->assertSame(
            [RefundType::OnlyRefund],
            service(RefundService::class)->getAllowedRefundTypes($order)
        );

        $refund = service(RefundService::class)->createRefund(
            $order,
            $this->user,
            $this->refundData($item, RefundType::OnlyRefund)
        );

        $this->assertSame(RefundStatus::Processing, $refund->status);
    }

    public function test_pending_pickup_rejects_return_refund(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::PickupPending, FulfillmentType::Pickup);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('待自提订单尚未交付，请选择仅退款');

        service(RefundService::class)->createRefund(
            $order,
            $this->user,
            $this->refundData($item, RefundType::ReturnRefund)
        );
    }

    public function test_verified_pickup_allows_return_refund_only(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Verified, FulfillmentType::Pickup);

        $this->assertSame(
            [RefundType::ReturnRefund],
            service(RefundService::class)->getAllowedRefundTypes($order)
        );

        $refund = service(RefundService::class)->createRefund(
            $order,
            $this->user,
            $this->refundData($item, RefundType::ReturnRefund)
        );

        $this->assertSame(RefundStatus::Pending, $refund->status, '退货退款需商家审核');
    }

    public function test_completed_mail_order_is_not_refundable(): void
    {
        [$order] = $this->makeOrder(OrderStatus::Completed, FulfillmentType::Mail);

        $this->assertSame([], service(RefundService::class)->getAllowedRefundTypes($order));
        $this->assertFalse(service(RefundService::class)->isOrderRefundable($order));
    }

    public function test_payment_refund_rejection_marks_after_sales_refund_failed(): void
    {
        [$order, $item] = $this->makeOrder(OrderStatus::Completed, FulfillmentType::Virtual);

        $this->ensurePaidPayment($order);

        $refund = service(RefundService::class)->createRefund(
            $order,
            $this->user,
            $this->refundData($item, RefundType::OnlyRefund)
        );

        service(RefundService::class)->confirmRefund($refund, $this->user);

        $paymentRefund = $refund->paymentRefunds()->latest('id')->firstOrFail();
        $approver = Administrator::factory()->create();

        service(PaymentRefundService::class)->reject($paymentRefund, $approver->id, '财务驳回');

        $this->assertSame(RefundStatus::Failed, $refund->fresh()->status);
        $this->assertNotSame(OrderStatus::Refunded, $order->fresh()->status);
    }

    public function test_canceled_order_received_payment_creates_refund_instead_of_failing(): void
    {
        [$order, $item, $sku] = $this->makeOrder(OrderStatus::Pending, FulfillmentType::Mail, 1);

        // 模拟下单时已扣库存，取消时应回补
        $sku->deductStock(1);

        service(OrderService::class)->cancel($order, $this->user);

        $this->assertSame(OrderStatus::Canceled, $order->fresh()->status);

        $payment = PaymentOrder::create([
            'tenant_id' => $order->tenant_id,
            'user_id' => $order->user_id,
            'gateway' => PaymentGateway::Balance,
            'amount' => $order->getTotalAmount(),
            'expired_at' => now()->addMinutes(30),
            'paymentable' => $order,
        ]);

        service(PaymentService::class)->markPaidWithBusiness($payment, $this->user);

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);

        $paymentRefund = PaymentRefund::where('source_type', $order->getMorphClass())
            ->where('source_id', $order->getKey())
            ->first();

        $this->assertNotNull($paymentRefund, '已取消订单收到付款时应自动发起原路退款');
        $this->assertSame(PaymentRefundStatus::Pending, $paymentRefund->status);
        $this->assertSame(OrderStatus::Canceled, $order->fresh()->status);
    }

    public function test_pickup_timeout_command_creates_refund_for_unverified_order(): void
    {
        $this->seed(SystemTableSeeder::class);

        [$order, , $sku] = $this->makeOrder(OrderStatus::PickupPending, FulfillmentType::Pickup, 1);

        $order->update(['paid_at' => now()->subDays(8)]);
        $sku->deductStock(1);
        $this->ensurePaidPayment($order);

        $this->artisan('app:mall:pickup-timeout')->assertSuccessful();

        $refund = Refund::where('order_id', $order->id)->first();

        $this->assertNotNull($refund, '超时未核销的自提订单应自动发起退款');
        $this->assertSame(RefundStatus::Processing, $refund->status);
        $this->assertSame(1, $refund->paymentRefunds()->count());
        $this->assertSame(PaymentRefundStatus::Pending, $refund->paymentRefunds()->first()->status);
    }

    /**
     * 创建订单与订单项（不触发下单/支付流程）
     *
     * @return array{0: Order, 1: OrderItem, 2: Sku}
     */
    private function makeOrder(
        OrderStatus $status,
        FulfillmentType $fulfillmentType,
        int $qty = 1,
        int $stock = 10,
    ): array {
        $product = Product::factory()->for($this->tenant, 'tenant')->create([
            'deduct_stock_type' => DeductStockType::Ordered,
            'fulfillment_type' => [$fulfillmentType->value],
        ]);

        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'price' => '88.00',
            'stock' => $stock,
        ]);

        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => $status,
            'fulfillment_type' => $fulfillmentType,
            'amount' => bcmul('88.00', (string) $qty, 2),
            'freight' => 0,
            'coupon_discount' => 0,
            'paid_at' => $status === OrderStatus::Pending ? null : now(),
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

    private function refundData(OrderItem $item, RefundType $type): RefundData
    {
        return RefundData::make(
            type: $type,
            reason: RefundReason::NotWant,
            items: [RefundItemData::make($item->id, $item->qty)],
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
