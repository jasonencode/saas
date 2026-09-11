<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\InvoiceTitleType;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Finance\InvoiceTitle;
use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Finance\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

/**
 * 开票订单校验：未支付/已取消/已申请退款的订单不可开票
 */
class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private InvoiceService $service;

    private InvoiceTitle $invoiceTitle;

    protected function setUp(): void
    {
        parent::setUp();

        // 开票申请会触发通知（依赖 Redis 广播通道），测试中拦截通知发送
        Notification::fake();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->service = app(InvoiceService::class);

        $this->invoiceTitle = InvoiceTitle::create([
            'user_id' => $this->user->getKey(),
            'tenant_id' => $this->tenant->id,
            'type' => InvoiceTitleType::Personal,
            'title' => '张三',
        ]);
    }

    // ========================================
    // 测试夹具
    // ========================================

    /**
     * 创建订单
     */
    private function makeOrder(
        OrderStatus $status = OrderStatus::Paid,
        string $amount = '100.00',
        string $freight = '0.00',
        string $couponDiscount = '0.00'
    ): Order {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->getKey(),
            'amount' => $amount,
            'freight' => $freight,
            'coupon_discount' => $couponDiscount,
            'status' => $status,
            'fulfillment_type' => FulfillmentType::Mail,
        ]);

        $order->items()->create([
            'orderable_type' => 'App\\Models\\Mall\\Sku',
            'orderable_id' => 1,
            'orderable_name' => '测试商品',
            'qty' => 1,
            'price' => $amount,
        ]);

        return $order->load('items');
    }

    /**
     * 为订单写入一张退款单
     */
    private function makeRefund(Order $order, RefundStatus $status): Refund
    {
        return Refund::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->getKey(),
            'order_id' => $order->getKey(),
            'total' => '100.00',
            'goods_amount' => '100.00',
            'freight_amount' => '0.00',
            'status' => $status,
            'type' => RefundType::OnlyRefund,
            'reason' => RefundReason::NotWant,
        ]);
    }

    /**
     * 提交开票申请
     */
    private function applyFor(Order $order): void
    {
        $this->service->createApplication($this->user->getKey(), $this->tenant->id, [
            'invoice_title_id' => $this->invoiceTitle->getKey(),
            'reason' => '报销',
            'order_ids' => [$order->getKey()],
        ]);
    }

    // ========================================
    // 状态校验
    // ========================================

    public function test_paid_order_without_refund_can_be_invoiced(): void
    {
        $order = $this->makeOrder();

        $this->applyFor($order);

        $this->assertDatabaseHas('invoice_applications', [
            'user_id' => $this->user->getKey(),
            'amount' => 100.00,
        ]);
    }

    public function test_canceled_order_cannot_be_invoiced(): void
    {
        $order = $this->makeOrder(OrderStatus::Canceled);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('订单未支付或已取消，不可开票');

        $this->applyFor($order);
    }

    public function test_unpaid_order_cannot_be_invoiced(): void
    {
        $order = $this->makeOrder(OrderStatus::Pending);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('订单未支付或已取消，不可开票');

        $this->applyFor($order);
    }

    // ========================================
    // 退款校验
    // ========================================

    public function test_order_with_active_refund_cannot_be_invoiced(): void
    {
        $order = $this->makeOrder();
        $this->makeRefund($order, RefundStatus::Processing);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('订单已申请退款，不可开票');

        $this->applyFor($order);
    }

    public function test_fully_refunded_order_cannot_be_invoiced(): void
    {
        // 全额退款后订单状态为已签收/已完成，仅靠状态无法识别，须由退款记录拦截
        $order = $this->makeOrder(OrderStatus::Completed);
        $this->makeRefund($order, RefundStatus::Completed);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('订单已申请退款，不可开票');

        $this->applyFor($order);
    }

    public function test_order_with_rejected_refund_can_be_invoiced(): void
    {
        // 退款被拒绝后不占用订单，恢复可开票
        $order = $this->makeOrder();
        $this->makeRefund($order, RefundStatus::Rejected);

        $this->applyFor($order);

        $this->assertDatabaseHas('invoice_applications', [
            'user_id' => $this->user->getKey(),
            'amount' => 100.00,
        ]);
    }

    public function test_coupon_order_invoice_amount_is_net_of_discount(): void
    {
        // 券订单开票金额按实付口径：商品 100 + 运费 10 − 券 20 = 90
        $order = $this->makeOrder(amount: '100.00', freight: '10.00', couponDiscount: '20.00');

        $this->applyFor($order);

        $this->assertDatabaseHas('invoice_applications', [
            'user_id' => $this->user->getKey(),
            'amount' => 90.00,
        ]);
    }

    // ========================================
    // 可开票订单列表（口径须与校验一致）
    // ========================================

    public function test_invoicable_orders_list_excludes_refunded_and_canceled(): void
    {
        $paid = $this->makeOrder();
        $refunded = $this->makeOrder();
        $this->makeRefund($refunded, RefundStatus::Processing);
        $rejected = $this->makeOrder();
        $this->makeRefund($rejected, RefundStatus::Rejected);
        $canceled = $this->makeOrder(OrderStatus::Canceled);

        $response = $this->actingAs($this->user)->getJson('/api/user/invoices/orders');

        $response->assertOk();

        $orderIds = collect($response->json('list'))->pluck('order_id')->all();

        $this->assertContains($paid->getKey(), $orderIds);
        // 退款被拒绝后恢复可开票
        $this->assertContains($rejected->getKey(), $orderIds);
        $this->assertNotContains($refunded->getKey(), $orderIds);
        $this->assertNotContains($canceled->getKey(), $orderIds);
    }
}
