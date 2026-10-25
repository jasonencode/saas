<?php

namespace Tests\Feature\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Mall\DTOs\RefundData;
use App\Services\Mall\DTOs\RefundItemData;
use App\Services\Mall\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundAutoApproveTest extends TestCase
{
    use RefreshDatabase;

    private RefundService $service;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RefundService::class);
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->user->tenants()->attach($this->tenant);
    }

    private function createOrderWithItem(OrderStatus $status): Order
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => $status,
            'fulfillment_type' => FulfillmentType::Mail,
            'amount' => '100.00',
            'freight' => '0.00',
        ]);

        // 租户归属经product 关联传导（skus 表无 tenant_id 列），
        // 退款链路依赖 product->name 与 product->deduct_stock_type，故建真实 product
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $sku = Sku::factory()->create(['product_id' => $product->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'orderable_type' => $sku->getMorphClass(),
            'orderable_id' => $sku->id,
            'qty' => 1,
            'price' => '100.00',
        ]);

        return $order->load('items');
    }

    /**
     * 按订单状态构造退款数据
     *
     * 已发货/已签收/已完成订单业务上只支持退货退款（仅退款会被
     * RefundService::validateRefundType 拒绝），未发货订单用仅退款。
     */
    private function makeRefundData(Order $order): RefundData
    {
        $shipped = in_array($order->status, [
            OrderStatus::Delivered,
            OrderStatus::Signed,
            OrderStatus::Completed,
        ], true);

        return RefundData::make(
            type: $shipped ? RefundType::ReturnRefund : RefundType::OnlyRefund,
            reason: RefundReason::NotWant,
            items: [RefundItemData::make(orderItemId: $order->items->first()->id, qty: 1)],
        );
    }

    public function test_paid_order_auto_approves_refund(): void
    {
        $order = $this->createOrderWithItem(OrderStatus::Paid);
        $refund = $this->service->createRefund($order, $this->user, $this->makeRefundData($order));

        $this->assertSame(RefundStatus::Processing, $refund->status);
        $this->assertNotNull($refund->approved_at);
        $this->assertCount(3, $refund->eventLogs);
        $this->assertSame('refund.applied', $refund->eventLogs[0]->event);
        $this->assertSame('refund.approved', $refund->eventLogs[1]->event);
        $this->assertSame('refund.processing', $refund->eventLogs[2]->event);
    }

    public function test_preparing_order_auto_approves_refund(): void
    {
        $order = $this->createOrderWithItem(OrderStatus::Preparing);
        $refund = $this->service->createRefund($order, $this->user, $this->makeRefundData($order));

        $this->assertSame(RefundStatus::Processing, $refund->status);
        $this->assertNotNull($refund->approved_at);
    }

    public function test_delivered_order_does_not_auto_approve(): void
    {
        $order = $this->createOrderWithItem(OrderStatus::Delivered);
        $refund = $this->service->createRefund($order, $this->user, $this->makeRefundData($order));

        $this->assertSame(RefundStatus::Pending, $refund->status);
        $this->assertNull($refund->approved_at);
        $this->assertCount(1, $refund->eventLogs);
        $this->assertSame('refund.applied', $refund->eventLogs[0]->event);
    }

    public function test_signed_order_does_not_auto_approve(): void
    {
        $order = $this->createOrderWithItem(OrderStatus::Signed);
        $refund = $this->service->createRefund($order, $this->user, $this->makeRefundData($order));

        $this->assertSame(RefundStatus::Pending, $refund->status);
        $this->assertNull($refund->approved_at);
    }
}
