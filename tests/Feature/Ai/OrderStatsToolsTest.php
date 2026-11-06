<?php

namespace Tests\Feature\Ai;

use App\Ai\Tools\GetPendingFulfillment;
use App\Ai\Tools\GetProductSalesReport;
use App\Ai\Tools\GetRefundAnalysis;
use App\Ai\Tools\GetSalesTrend;
use App\Ai\Tools\GetTodayOrderStats;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Product;
use App\Models\Mall\ProductCategory;
use App\Models\Mall\Refund;
use App\Models\Mall\RefundItem;
use App\Models\System\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class OrderStatsToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_stats_are_scoped_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $paid = Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'amount' => 100,
            'freight' => 10,
            'coupon_discount' => 5,
        ]);

        OrderItem::factory()->create([
            'order_id' => $paid->id,
            'qty' => 2,
            'price' => 50,
        ]);

        Order::factory()->pending()->create(['tenant_id' => $tenant->id]);
        Order::factory()->canceled()->create(['tenant_id' => $tenant->id]);

        $otherPaid = Order::factory()->paid()->create([
            'tenant_id' => $otherTenant->id,
            'amount' => 999,
        ]);

        Refund::create([
            'tenant_id' => $tenant->id,
            'user_id' => $paid->user_id,
            'order_id' => $paid->id,
            'type' => RefundType::OnlyRefund,
            'total' => 30,
            'goods_amount' => 30,
            'freight_amount' => 0,
            'status' => RefundStatus::Completed,
            'refund_at' => now(),
        ]);

        Refund::create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $otherPaid->user_id,
            'order_id' => $otherPaid->id,
            'type' => RefundType::OnlyRefund,
            'total' => 500,
            'goods_amount' => 500,
            'freight_amount' => 0,
            'status' => RefundStatus::Completed,
            'refund_at' => now(),
        ]);

        $data = json_decode((string) (new GetTodayOrderStats($tenant->id))->handle(new Request), true);

        $this->assertSame(3, $data['orders']['created']);
        $this->assertSame(1, $data['orders']['canceled']);
        $this->assertSame(2, $data['orders']['items_quantity']);
        $this->assertEquals(105.0, $data['payment']['paid_amount']);
        $this->assertSame(1, $data['payment']['paid_orders']);
        $this->assertEquals(30.0, $data['refund']['amount']);
        $this->assertSame(1, $data['pending']['shipment']);
    }

    public function test_product_report_groups_by_product_and_category_with_refunds(): void
    {
        $tenant = Tenant::factory()->create();
        $category = ProductCategory::factory()->create(['name' => '数码']);
        $product = Product::factory()->create(['category_id' => $category->id, 'name' => '相机']);
        $otherProduct = Product::factory()->create();

        $order = Order::factory()->paid()->create(['tenant_id' => $tenant->id]);

        $firstItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'orderable_type' => Product::class,
            'orderable_id' => $product->id,
            'orderable_name' => '相机',
            'qty' => 2,
            'price' => 100,
            'coupon_discount' => 10,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'orderable_type' => Product::class,
            'orderable_id' => $product->id,
            'orderable_name' => '相机',
            'qty' => 1,
            'price' => 100,
            'coupon_discount' => 0,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'orderable_type' => Product::class,
            'orderable_id' => $otherProduct->id,
            'orderable_name' => '三脚架',
            'qty' => 1,
            'price' => 50,
            'coupon_discount' => 0,
        ]);

        $otherOrder = Order::factory()->paid()->create(['tenant_id' => Tenant::factory()->create()->id]);

        OrderItem::factory()->create([
            'order_id' => $otherOrder->id,
            'orderable_type' => Product::class,
            'orderable_id' => $product->id,
            'orderable_name' => '相机',
            'qty' => 100,
            'price' => 100,
        ]);

        $refund = Refund::create([
            'tenant_id' => $tenant->id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'type' => RefundType::OnlyRefund,
            'total' => 100,
            'goods_amount' => 100,
            'freight_amount' => 0,
            'status' => RefundStatus::Processing,
        ]);

        RefundItem::create([
            'refund_id' => $refund->id,
            'order_item_id' => $firstItem->id,
            'qty' => 1,
            'price' => 100,
        ]);

        $report = json_decode((string) (new GetProductSalesReport($tenant->id))->handle(
            new Request(['period' => 'today'])
        ), true);

        $this->assertSame(4, $report['totals']['qty']);
        $this->assertEquals(340.0, $report['totals']['amount']);
        $this->assertEquals(100.0, $report['totals']['refund_amount']);
        $this->assertEquals(240.0, $report['totals']['net_amount']);

        $camera = collect($report['items'])->firstWhere('name', '相机');

        $this->assertSame('数码', $camera['category']);
        $this->assertSame(3, $camera['qty']);
        $this->assertEquals(290.0, $camera['amount']);
        $this->assertSame(1, $camera['refund_qty']);
        $this->assertEquals(190.0, $camera['net_amount']);

        $categoryReport = json_decode((string) (new GetProductSalesReport($tenant->id))->handle(
            new Request(['period' => 'today', 'dimension' => 'category'])
        ), true);

        $this->assertSame('数码', $categoryReport['items'][0]['name']);
        $this->assertSame(3, $categoryReport['items'][0]['qty']);
        $this->assertEquals(290.0, $categoryReport['items'][0]['amount']);
    }

    public function test_sales_trend_fills_buckets_and_compares_previous_period(): void
    {
        $tenant = Tenant::factory()->create();

        $today = Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'amount' => 100,
        ]);

        Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'amount' => 50,
            'paid_at' => now()->subDays(3),
        ]);

        Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'amount' => 200,
            'paid_at' => now()->subDays(10),
        ]);

        Order::factory()->paid()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'amount' => 999,
        ]);

        Refund::create([
            'tenant_id' => $tenant->id,
            'user_id' => $today->user_id,
            'order_id' => $today->id,
            'type' => RefundType::OnlyRefund,
            'total' => 20,
            'goods_amount' => 20,
            'freight_amount' => 0,
            'status' => RefundStatus::Processing,
        ]);

        $trend = json_decode((string) (new GetSalesTrend($tenant->id))->handle(
            new Request(['period' => 'week'])
        ), true);

        $this->assertSame('day', $trend['period']['granularity']);
        $this->assertCount(7, $trend['buckets']);
        $this->assertSame(2, $trend['totals']['orders']);
        $this->assertEquals(150.0, $trend['totals']['amount']);
        $this->assertEquals(20.0, $trend['totals']['refund_amount']);
        $this->assertEquals(130.0, $trend['totals']['net_amount']);

        $this->assertEquals(200.0, $trend['comparison']['amount']);
        $this->assertEquals(-25.0, $trend['comparison']['amount_change']);
    }

    public function test_refund_analysis_groups_by_reason_and_product(): void
    {
        $tenant = Tenant::factory()->create();
        $order = Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'amount' => 1000,
        ]);

        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'orderable_type' => Product::class,
            'orderable_id' => Product::factory()->create()->id,
            'orderable_name' => '相机',
            'qty' => 3,
            'price' => 100,
        ]);

        $quality = Refund::create([
            'tenant_id' => $tenant->id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'type' => RefundType::OnlyRefund,
            'total' => 300,
            'goods_amount' => 300,
            'freight_amount' => 0,
            'status' => RefundStatus::Processing,
            'reason' => RefundReason::QualityIssue,
        ]);

        RefundItem::create([
            'refund_id' => $quality->id,
            'order_item_id' => $item->id,
            'qty' => 2,
            'price' => 100,
        ]);

        $notWant = Refund::create([
            'tenant_id' => $tenant->id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'type' => RefundType::OnlyRefund,
            'total' => 100,
            'goods_amount' => 100,
            'freight_amount' => 0,
            'status' => RefundStatus::Completed,
            'reason' => RefundReason::NotWant,
            'refund_at' => now(),
        ]);

        RefundItem::create([
            'refund_id' => $notWant->id,
            'order_item_id' => $item->id,
            'qty' => 1,
            'price' => 100,
        ]);

        Refund::create([
            'tenant_id' => $tenant->id,
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'type' => RefundType::OnlyRefund,
            'total' => 500,
            'goods_amount' => 500,
            'freight_amount' => 0,
            'status' => RefundStatus::Rejected,
            'reason' => RefundReason::Other,
        ]);

        $analysis = json_decode((string) (new GetRefundAnalysis($tenant->id))->handle(
            new Request(['period' => 'today'])
        ), true);

        $this->assertSame(2, $analysis['totals']['count']);
        $this->assertEquals(400.0, $analysis['totals']['amount']);
        $this->assertEquals(40.0, $analysis['totals']['refund_rate']);
        $this->assertSame(1, $analysis['totals']['completed_count']);

        $qualityRow = collect($analysis['by_reason'])->firstWhere('reason', '质量问题');

        $this->assertEquals(300.0, $qualityRow['amount']);
        $this->assertEquals(75.0, $qualityRow['share']);

        $productRow = $analysis['top_products'][0];

        $this->assertSame('相机', $productRow['name']);
        $this->assertSame(3, $productRow['qty']);
        $this->assertEquals(300.0, $productRow['amount']);
    }

    public function test_pending_fulfillment_buckets_orders_by_waiting_time(): void
    {
        $tenant = Tenant::factory()->create();

        Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'paid_at' => now()->subHour(),
        ]);

        Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'paid_at' => now()->subHours(30),
        ]);

        $oldest = Order::factory()->paid()->create([
            'tenant_id' => $tenant->id,
            'paid_at' => now()->subHours(100),
        ]);

        Order::factory()->withPickup()->create([
            'tenant_id' => $tenant->id,
            'status' => OrderStatus::PickupPending,
            'paid_at' => now()->subHours(2),
        ]);

        Order::factory()->paid()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'paid_at' => now()->subHours(100),
        ]);

        $data = json_decode((string) (new GetPendingFulfillment($tenant->id))->handle(new Request), true);

        $this->assertSame(3, $data['shipment']['count']);
        $this->assertSame(1, $data['shipment']['age']['within_24h']);
        $this->assertSame(1, $data['shipment']['age']['between_24h_72h']);
        $this->assertSame(1, $data['shipment']['age']['over_72h']);
        $this->assertSame($oldest->no, $data['shipment']['oldest'][0]['order_no']);
        $this->assertEqualsWithDelta(100.0, $data['shipment']['oldest'][0]['waiting_hours'], 0.1);

        $this->assertSame(1, $data['pickup']['count']);
        $this->assertSame(1, $data['pickup']['age']['within_24h']);
    }
}
