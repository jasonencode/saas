<?php

namespace Tests\Feature\Ai;

use App\Ai\Tools\GetOrderAnomalies;
use App\Ai\Tools\GetOrderDetail;
use App\Ai\Tools\GetOrderTimeline;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Mall\Order;
use App\Models\Mall\OrderAddress;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Refund;
use App\Models\System\Administrator;
use App\Models\System\EventLog;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class OrderFollowUpToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_detail_returns_full_chain(): void
    {
        $tenant = Tenant::factory()->create();

        $order = Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'amount' => 200,
            'freight' => 10,
            'coupon_discount' => 20,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'orderable_name' => '相机',
            'qty' => 2,
            'price' => 100,
        ]);

        OrderAddress::factory()->create([
            'order_id' => $order->id,
            'name' => '张三',
            'mobile' => '13800001234',
        ]);

        $detail = json_decode((string) (new GetOrderDetail($tenant->id))->handle(
            new Request(['no' => $order->no])
        ), true);

        $this->assertSame($order->no, $detail['order_no']);
        $this->assertSame('待发货', $detail['status']);
        $this->assertEquals(190.0, $detail['amounts']['total']);
        $this->assertSame('相机', $detail['items'][0]['name']);
        $this->assertSame(2, $detail['items'][0]['qty']);
        $this->assertSame('138****1234', $detail['address']['mobile']);
    }

    public function test_order_detail_hides_other_tenants(): void
    {
        $order = Order::factory()->paid()->create([
            'tenant_id' => Tenant::factory()->create()->id,
        ]);

        $result = (new GetOrderDetail(Tenant::factory()->create()->id))->handle(
            new Request(['no' => $order->no])
        );

        $this->assertStringContainsString('未找到订单', (string) $result);
    }

    public function test_order_timeline_returns_events_and_key_times(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = Administrator::factory()->create(['name' => '运营小李']);
        $order = Order::factory()->paid()->create(['tenant_id' => $tenant->id]);

        EventLog::create([
            'event' => 'order.created',
            'action' => 'create',
            'description' => '创建订单',
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'actor_type' => Administrator::class,
            'actor_id' => $admin->id,
            'created_at' => now()->subMinutes(10),
        ]);

        EventLog::create([
            'event' => 'order.paid',
            'action' => 'pay',
            'description' => '订单支付成功',
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'actor_type' => Administrator::class,
            'actor_id' => $admin->id,
            'created_at' => now()->subMinutes(5),
        ]);

        $timeline = json_decode((string) (new GetOrderTimeline($tenant->id))->handle(
            new Request(['no' => $order->no])
        ), true);

        $this->assertSame(2, $timeline['events_total']);
        $this->assertSame('order.created', $timeline['events'][0]['event']);
        $this->assertSame('order.paid', $timeline['events'][1]['event']);
        $this->assertSame('运营小李', $timeline['events'][0]['actor']);
        $this->assertSame('管理员', $timeline['events'][0]['actor_type']);
        $this->assertNotNull($timeline['key_times']['paid_at']);
    }

    public function test_order_anomalies_detect_stalled_refunding_duplicate_and_zero_amount(): void
    {
        $tenant = Tenant::factory()->create();

        $stalled = Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'updated_at' => now()->subHours(100),
        ]);

        $refunding = Order::factory()->paid()->create(['tenant_id' => $tenant->id]);

        Refund::create([
            'tenant_id' => $tenant->id,
            'user_id' => $refunding->user_id,
            'order_id' => $refunding->id,
            'type' => RefundType::OnlyRefund,
            'total' => 50,
            'goods_amount' => 50,
            'freight_amount' => 0,
            'status' => RefundStatus::Pending,
        ]);

        $duplicateUser = User::factory()->create();

        foreach (range(1, 3) as $index) {
            Order::factory()->paid()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $duplicateUser->id,
            ]);
        }

        $zeroAmount = Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'amount' => 0,
            'freight' => 0,
            'coupon_discount' => 0,
        ]);

        Order::factory()->paid()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'updated_at' => now()->subHours(100),
        ]);

        $data = json_decode((string) (new GetOrderAnomalies($tenant->id))->handle(new Request), true);

        $this->assertSame(1, $data['stalled']['count']);
        $this->assertSame($stalled->no, $data['stalled']['orders'][0]['order_no']);

        $this->assertSame(1, $data['refund_pending_shipment']['count']);
        $this->assertSame($refunding->no, $data['refund_pending_shipment']['orders'][0]['order_no']);
        $this->assertSame(1, $data['refund_pending_shipment']['orders'][0]['active_refunds']);

        $this->assertSame($duplicateUser->id, $data['duplicate_orders']['users'][0]['user_id']);
        $this->assertSame(3, $data['duplicate_orders']['users'][0]['orders']);

        $this->assertSame(1, $data['zero_amount']['count']);
        $this->assertSame($zeroAmount->no, $data['zero_amount']['orders'][0]['order_no']);
    }
}
