<?php

namespace Tests\Feature\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Express;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\OrderShipping;
use App\Models\System\Tenant;
use App\Models\User\User;
use App\Services\Mall\OrderService;
use Database\Seeders\SystemTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * 订单服务补充动作：改运费、取消发货、改物流、延时签收、自动签收。
 */
class OrderOperationsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Express $express;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
        $this->express = Express::factory()->create([
            'name' => '顺丰速运',
            'code' => 'SF',
        ]);
    }

    public function test_update_freight_only_allows_pending_order(): void
    {
        $order = $this->makeOrder(OrderStatus::Pending);

        service(OrderService::class)->updateFreight($order, 12.5, $this->user);

        $this->assertSame('12.50', $order->fresh()->freight);
        $this->assertDatabaseHas('event_logs', [
            'event' => 'order.freight_updated',
            'subject_id' => $order->id,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('当前订单状态不可修改运费');

        service(OrderService::class)->updateFreight($this->makeOrder(OrderStatus::Paid), 10, $this->user);
    }

    public function test_update_freight_rejects_negative_amount(): void
    {
        $this->expectExceptionMessage('运费不能小于 0');

        service(OrderService::class)->updateFreight($this->makeOrder(OrderStatus::Pending), -1, $this->user);
    }

    public function test_cancel_deliver_rolls_back_partially_then_fully(): void
    {
        $order = $this->makeOrder(OrderStatus::Delivered);
        $first = $this->makeItem($order);
        $second = $this->makeItem($order);

        $shippingA = OrderShipping::factory()->for($order)->create(['express_id' => $this->express->id]);
        $shippingB = OrderShipping::factory()->for($order)->create(['express_id' => $this->express->id]);

        $first->update(['order_shipping_id' => $shippingA->id]);
        $second->update(['order_shipping_id' => $shippingB->id]);

        service(OrderService::class)->cancelDeliver($order, $this->user, collect([$shippingA->fresh()]));

        $this->assertSoftDeleted('order_shippings', ['id' => $shippingA->id]);
        $this->assertNull($first->fresh()->order_shipping_id);
        $this->assertSame($shippingB->id, $second->fresh()->order_shipping_id);
        $this->assertSame(OrderStatus::PartiallyShipped, $order->fresh()->status);

        service(OrderService::class)->cancelDeliver($order, $this->user);

        $this->assertSoftDeleted('order_shippings', ['id' => $shippingB->id]);
        $this->assertNull($second->fresh()->order_shipping_id);
        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertNull($order->fresh()->auto_sign_at);
        $this->assertDatabaseHas('event_logs', [
            'event' => 'order.deliver_canceled',
            'subject_id' => $order->id,
        ]);
    }

    public function test_cancel_deliver_rejects_pending_order(): void
    {
        $this->expectExceptionMessage('当前订单状态不可取消发货');

        service(OrderService::class)->cancelDeliver($this->makeOrder(OrderStatus::Pending), $this->user);
    }

    public function test_update_express_changes_shipping_information(): void
    {
        $order = $this->makeOrder(OrderStatus::Delivered);
        $item = $this->makeItem($order);
        $shipping = OrderShipping::factory()->for($order)->create([
            'express_id' => $this->express->id,
            'express_no' => 'SF0001',
        ]);
        $item->update(['order_shipping_id' => $shipping->id]);

        $newExpress = Express::factory()->create(['name' => '中通快递', 'code' => 'ZTO']);

        service(OrderService::class)->updateExpress($order, [
            $shipping->id => ['express_id' => $newExpress->id, 'express_no' => 'ZTO0002'],
        ], $this->user);

        $shipping->refresh();
        $this->assertSame($newExpress->id, $shipping->express_id);
        $this->assertSame('ZTO0002', $shipping->express_no);
        $this->assertDatabaseHas('event_logs', [
            'event' => 'order.express_updated',
            'subject_id' => $order->id,
        ]);
    }

    public function test_delay_sign_extends_auto_sign_time(): void
    {
        $order = $this->makeOrder(OrderStatus::Delivered);
        $order->update(['auto_sign_at' => now()->addDays(2)]);

        $deadline = $order->fresh()->auto_sign_at;

        service(OrderService::class)->delaySign($order, $this->user);

        $this->assertTrue($order->fresh()->auto_sign_at->equalTo($deadline->copy()->addDays(7)));
        $this->assertDatabaseHas('event_logs', [
            'event' => 'order.sign_delayed',
            'subject_id' => $order->id,
        ]);
    }

    public function test_delay_sign_rejects_expired_order(): void
    {
        $order = $this->makeOrder(OrderStatus::Delivered);
        $order->update(['auto_sign_at' => now()->subDays(8)]);

        $this->expectExceptionMessage('订单超出可延时签收期限');

        service(OrderService::class)->delaySign($order, $this->user);
    }

    public function test_deliver_sets_auto_sign_time(): void
    {
        $order = $this->makeOrder(OrderStatus::Paid);
        $item = $this->makeItem($order);

        service(OrderService::class)->deliver($order, [$item->id], $this->express->id, 'SF123456', $this->user);

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertNotNull($order->auto_sign_at);
        $this->assertTrue($order->auto_sign_at->greaterThan(now()->addDays(6)));
    }

    public function test_auto_sign_command_signs_overdue_orders(): void
    {
        $this->seed(SystemTableSeeder::class);

        $order = $this->makeOrder(OrderStatus::Delivered);
        $order->update(['auto_sign_at' => now()->subMinute()]);

        $this->artisan('app:mall:order-auto-sign')->assertSuccessful();

        $this->assertSame(OrderStatus::Signed, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->signed_at);
    }

    private function makeOrder(OrderStatus $status): Order
    {
        return Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'status' => $status,
            'fulfillment_type' => FulfillmentType::Mail,
            'amount' => '88.00',
            'freight' => '0.00',
            'coupon_discount' => '0.00',
            'paid_at' => $status === OrderStatus::Pending ? null : now(),
        ]);
    }

    private function makeItem(Order $order): OrderItem
    {
        return OrderItem::factory()->create([
            'order_id' => $order->id,
            'qty' => 1,
            'price' => '88.00',
            'coupon_discount' => 0,
        ]);
    }
}
