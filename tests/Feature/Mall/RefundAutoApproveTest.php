<?php

namespace Tests\Feature\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderLogAction;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundLogAction;
use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
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
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
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

        $sku = Sku::factory()->create(['tenant_id' => $this->tenant->id]);

        OrderItem::create([
            'order_id' => $order->id,
            'orderable_type' => $sku->getMorphClass(),
            'orderable_id' => $sku->id,
            'qty' => 1,
            'price' => '100.00',
        ]);

        return $order->load('items');
    }

    private function makeRefundData(int $orderItemId): RefundData
    {
        return RefundData::make(
            type: RefundType::OnlyRefund,
            reason: RefundReason::NotWant,
            items: [RefundItemData::make(orderItemId: $orderItemId, qty: 1)],
        );
    }

    public function test_paid_order_auto_approves_refund(): void
    {
        $order = $this->createOrderWithItem(OrderStatus::Paid);
        $refund = $this->service->createRefund($order, $this->user, $this->makeRefundData($order->items->first()->id));

        $this->assertSame(RefundStatus::Processing, $refund->status);
        $this->assertNotNull($refund->approved_at);
        $this->assertCount(3, $refund->logs);
        $this->assertSame(RefundLogAction::Created, $refund->logs[0]->action);
        $this->assertSame(RefundLogAction::Approved, $refund->logs[1]->action);
        $this->assertSame(RefundLogAction::Processing, $refund->logs[2]->action);
    }

    public function test_preparing_order_auto_approves_refund(): void
    {
        $order = $this->createOrderWithItem(OrderStatus::Preparing);
        $refund = $this->service->createRefund($order, $this->user, $this->makeRefundData($order->items->first()->id));

        $this->assertSame(RefundStatus::Processing, $refund->status);
        $this->assertNotNull($refund->approved_at);
    }

    public function test_delivered_order_does_not_auto_approve(): void
    {
        $order = $this->createOrderWithItem(OrderStatus::Delivered);
        $refund = $this->service->createRefund($order, $this->user, $this->makeRefundData($order->items->first()->id));

        $this->assertSame(RefundStatus::Pending, $refund->status);
        $this->assertNull($refund->approved_at);
        $this->assertCount(1, $refund->logs);
        $this->assertSame(RefundLogAction::Created, $refund->logs[0]->action);
    }

    public function test_signed_order_does_not_auto_approve(): void
    {
        $order = $this->createOrderWithItem(OrderStatus::Signed);
        $refund = $this->service->createRefund($order, $this->user, $this->makeRefundData($order->items->first()->id));

        $this->assertSame(RefundStatus::Pending, $refund->status);
        $this->assertNull($refund->approved_at);
    }
}
