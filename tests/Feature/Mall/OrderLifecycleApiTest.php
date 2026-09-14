<?php

namespace Tests\Feature\Mall;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 商城订单生命周期 API（下单预览 / 创建 / 详情 / 取消 / 删除 / 状态统计）
 */
class OrderLifecycleApiTest extends TestCase
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

        // 虚拟商品（免运费、免地址，便于金额断言）
        $this->product = Product::factory()->for($this->tenant, 'tenant')->create([
            'fulfillment_type' => [FulfillmentType::Virtual->value],
        ]);
        $this->sku = Sku::factory()->create([
            'product_id' => $this->product->id,
            'price' => '88.00',
            'stock' => 10,
        ]);
    }

    // ─── POST /api/mall/orders/preview ───────────────────────────

    public function test_can_preview_buy_now_order(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders/preview', [
            'orderable_type' => 'sku',
            'orderable_id' => $this->sku->id,
            'qty' => 2,
            'fulfillment_type' => FulfillmentType::Virtual->value,
        ])->assertOk()
            ->assertJsonPath('goods_amount', '176.00')
            ->assertJsonPath('freight', '0.00')
            ->assertJsonPath('payable_amount', '176.00');
    }

    public function test_preview_requires_authentication(): void
    {
        $this->postJson('/api/mall/orders/preview', [
            'orderable_type' => 'sku',
            'orderable_id' => $this->sku->id,
            'qty' => 1,
            'fulfillment_type' => FulfillmentType::Virtual->value,
        ])->assertUnauthorized();
    }

    public function test_preview_returns_error_for_missing_product(): void
    {
        Sanctum::actingAs($this->user);

        // orderable_id 存在性校验在 FormRequest 层拦截
        $this->postJson('/api/mall/orders/preview', [
            'orderable_type' => 'sku',
            'orderable_id' => 99999,
            'qty' => 1,
            'fulfillment_type' => FulfillmentType::Virtual->value,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('orderable_id');
    }

    // ─── POST /api/mall/orders ───────────────────────────────────

    public function test_can_create_order(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/mall/orders', [
            'orderable_type' => 'sku',
            'orderable_id' => $this->sku->id,
            'qty' => 2,
            'fulfillment_type' => FulfillmentType::Virtual->value,
        ])->assertCreated();

        $orders = $response->json();

        $this->assertCount(1, $orders);
        $this->assertSame(176.0, (float) $orders[0]['total_amount']);

        $order = Order::where('no', $orders[0]['no'])->first();
        $this->assertNotNull($order);
        $this->assertSame($this->user->id, $order->user_id);
        $this->assertSame($this->tenant->id, $order->tenant_id);
        $this->assertSame(OrderStatus::Pending, $order->status);

        // 默认 deduct_stock_type=paid（支付时扣库存），下单不减库存
        $this->assertSame(10, $this->sku->refresh()->stock);
    }

    public function test_create_order_requires_authentication(): void
    {
        $this->postJson('/api/mall/orders', [
            'orderable_type' => 'sku',
            'orderable_id' => $this->sku->id,
            'qty' => 1,
            'fulfillment_type' => FulfillmentType::Virtual->value,
        ])->assertUnauthorized();
    }

    public function test_create_order_requires_fulfillment_type(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders', [
            'orderable_type' => 'sku',
            'orderable_id' => $this->sku->id,
            'qty' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('fulfillment_type');
    }

    public function test_create_order_rejects_when_stock_insufficient(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders', [
            'orderable_type' => 'sku',
            'orderable_id' => $this->sku->id,
            'qty' => 99,
            'fulfillment_type' => FulfillmentType::Virtual->value,
        ])->assertStatus(400);

        $this->assertSame(0, Order::count());
    }

    // ─── GET /api/mall/orders ────────────────────────────────────

    public function test_can_list_own_orders(): void
    {
        $mine = $this->makeOrder();
        $other = $this->makeOrder(User::factory()->create());

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/mall/orders')
            ->assertOk()
            ->assertJsonStructure(['list', 'page' => ['current', 'total']]);

        $this->assertSame(1, $response->json('page.total'));
        $this->assertSame($mine->no, $response->json('list.0.no'));

        // 他人订单不出现在列表
        $nos = collect($response->json('list'))->pluck('no');
        $this->assertNotContains($other->no, $nos);
    }

    public function test_order_list_filters_by_scope(): void
    {
        $this->makeOrder();
        $this->makeOrder(status: OrderStatus::Canceled);

        Sanctum::actingAs($this->user);

        // pending scope 只返回待支付订单
        $this->getJson('/api/mall/orders?scope=pending')
            ->assertOk()
            ->assertJsonPath('page.total', 1);
    }

    public function test_order_list_filters_by_keyword(): void
    {
        $order = $this->makeOrder();

        Sanctum::actingAs($this->user);

        $this->getJson('/api/mall/orders?keyword='.$order->no)
            ->assertOk()
            ->assertJsonPath('page.total', 1);

        $this->getJson('/api/mall/orders?keyword=not-exists-no')
            ->assertOk()
            ->assertJsonPath('page.total', 0);
    }

    public function test_orders_requires_authentication(): void
    {
        $this->getJson('/api/mall/orders')->assertUnauthorized();
    }

    // ─── GET /api/mall/orders/status-count ───────────────────────

    public function test_status_count_returns_tab_counts(): void
    {
        $this->makeOrder();
        $this->makeOrder(status: OrderStatus::Canceled);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/mall/orders/status-count')
            ->assertOk();

        $this->assertSame(1, $response->json('pending'));
        $this->assertSame(0, $response->json('finished'));
        $this->assertSame(0, $response->json('available_coupons'));
    }

    public function test_status_count_requires_authentication(): void
    {
        $this->getJson('/api/mall/orders/status-count')->assertUnauthorized();
    }

    // ─── GET /api/mall/orders/{order} ────────────────────────────

    public function test_can_show_own_order(): void
    {
        $order = $this->makeOrder();
        Sanctum::actingAs($this->user);

        // 订单路由键为 no（订单号）；status 为 {value, label, color} 结构
        $this->getJson('/api/mall/orders/'.$order->no)
            ->assertOk()
            ->assertJsonPath('no', $order->no)
            ->assertJsonPath('status.value', OrderStatus::Pending->value);
    }

    public function test_show_returns_404_for_another_users_order(): void
    {
        $order = $this->makeOrder(User::factory()->create());
        Sanctum::actingAs($this->user);

        $this->getJson('/api/mall/orders/'.$order->no)
            ->assertNotFound();
    }

    public function test_show_returns_404_for_missing_order(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/mall/orders/99999')
            ->assertNotFound();
    }

    // ─── POST /api/mall/orders/{order}/cancel ────────────────────

    public function test_can_cancel_pending_order(): void
    {
        $order = $this->makeOrder();
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders/'.$order->no.'/cancel')
            ->assertNoContent();

        $this->assertSame(OrderStatus::Canceled, $order->refresh()->status);

        // 取消后库存回滚
        $this->assertSame(10, $this->sku->refresh()->stock);
    }

    public function test_cancel_forbidden_for_another_users_order(): void
    {
        $order = $this->makeOrder(User::factory()->create());
        Sanctum::actingAs($this->user);

        $this->postJson('/api/mall/orders/'.$order->no.'/cancel')
            ->assertForbidden();

        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
    }

    // ─── DELETE /api/mall/orders/{order} ─────────────────────────

    public function test_can_delete_own_canceled_order(): void
    {
        $order = $this->makeOrder(status: OrderStatus::Canceled);
        Sanctum::actingAs($this->user);

        $this->deleteJson('/api/mall/orders/'.$order->no)
            ->assertNoContent();

        $this->assertSoftDeleted('orders', [
            'id' => $order->id,
        ]);
    }

    public function test_delete_forbidden_for_another_users_order(): void
    {
        $order = $this->makeOrder(User::factory()->create());
        Sanctum::actingAs($this->user);

        $this->deleteJson('/api/mall/orders/'.$order->no)
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'deleted_at' => null,
        ]);
    }

    // ─── 测试夹具 ────────────────────────────────────────────────

    private function makeOrder(?User $user = null, OrderStatus $status = OrderStatus::Pending): Order
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => ($user ?? $this->user)->id,
            'amount' => '88.00',
            'freight' => '0.00',
            'coupon_discount' => '0.00',
            'fulfillment_type' => FulfillmentType::Virtual,
        ]);

        $order->items()->create([
            'orderable_type' => $this->sku->getMorphClass(),
            'orderable_id' => $this->sku->getKey(),
            'orderable_name' => '测试商品',
            'qty' => 1,
            'price' => '88.00',
        ]);

        if ($status !== OrderStatus::Pending) {
            $order->update(['status' => $status]);
        }

        return $order->fresh();
    }
}
