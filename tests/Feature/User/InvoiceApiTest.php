<?php

namespace Tests\Feature\User;

use App\Enums\Finance\InvoiceTitleType;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Models\Finance\InvoiceTitle;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 开票申请会触发通知（依赖 Redis 广播通道），测试中拦截通知发送
        Notification::fake();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
    }

    // ─── POST /api/user/invoice-titles ───────────────────────────

    public function test_can_create_invoice_title(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/invoice-titles', [
            'type' => InvoiceTitleType::Enterprise->value,
            'name' => '测试科技有限公司',
            'tax_no' => '91110000MA01ABCD5X',
            'is_default' => true,
        ])->assertCreated()
            ->assertJsonPath('name', '测试科技有限公司')
            ->assertJsonPath('type', InvoiceTitleType::Enterprise->value)
            ->assertJsonPath('is_default', true);

        $this->assertDatabaseHas('invoice_titles', [
            'user_id' => $this->user->id,
            'title' => '测试科技有限公司',
        ]);
    }

    public function test_create_invoice_title_requires_type_and_name(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/invoice-titles', [])
            ->assertUnprocessable()
            // BaseFormRequest 首错即停，只返回第一个错误字段
            ->assertJsonValidationErrors('type');
    }

    public function test_create_invoice_title_validates_tax_no_format(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/invoice-titles', [
            'type' => InvoiceTitleType::Enterprise->value,
            'name' => '测试科技有限公司',
            'tax_no' => 'invalid-tax!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('tax_no');
    }

    public function test_create_invoice_title_rejects_more_than_five_titles(): void
    {
        Sanctum::actingAs($this->user);

        for ($i = 0; $i < 5; $i++) {
            InvoiceTitle::create([
                'user_id' => $this->user->id,
                'type' => InvoiceTitleType::Personal,
                'title' => '抬头 '.$i,
            ]);
        }

        $this->postJson('/api/user/invoice-titles', [
            'type' => InvoiceTitleType::Personal->value,
            'name' => '第六个抬头',
        ])->assertStatus(400)
            ->assertJsonPath('code', 400);

        $this->assertSame(5, InvoiceTitle::where('user_id', $this->user->id)->count());
    }

    // ─── GET /api/user/invoice-titles ────────────────────────────

    public function test_can_list_own_invoice_titles(): void
    {
        $mine = $this->makeTitle('我的抬头', isDefault: true);
        $this->makeTitle('普通抬头');
        $other = User::factory()->create();
        $this->makeTitle('别人的抬头', userId: $other->id);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user/invoice-titles')
            ->assertOk();

        // 非分页集合序列化为平铺数组
        $names = collect($response->json())->pluck('name');

        $this->assertCount(2, $names);
        $this->assertContains('我的抬头', $names);
        $this->assertNotContains('别人的抬头', $names);

        // 默认抬头排在最前
        $this->assertSame($mine->getKey(), collect($response->json())->first()['title_id']);
    }

    // ─── GET /api/user/invoice-titles/{id} ───────────────────────

    public function test_can_show_own_invoice_title(): void
    {
        $title = $this->makeTitle('详情抬头');

        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/invoice-titles/'.$title->getKey())
            ->assertOk()
            ->assertJsonPath('name', '详情抬头');
    }

    public function test_cannot_show_another_users_invoice_title(): void
    {
        $other = User::factory()->create();
        $title = $this->makeTitle('别人的抬头', userId: $other->id);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/invoice-titles/'.$title->getKey())
            ->assertForbidden();
    }

    // ─── PUT /api/user/invoice-titles/{id} ───────────────────────

    public function test_can_update_own_invoice_title(): void
    {
        $title = $this->makeTitle('旧抬头');

        Sanctum::actingAs($this->user);

        $this->putJson('/api/user/invoice-titles/'.$title->getKey(), [
            'type' => InvoiceTitleType::Personal->value,
            'name' => '新抬头',
        ])->assertOk()
            ->assertJsonPath('name', '新抬头');

        $this->assertDatabaseHas('invoice_titles', [
            'id' => $title->getKey(),
            'title' => '新抬头',
        ]);
    }

    public function test_cannot_update_another_users_invoice_title(): void
    {
        $other = User::factory()->create();
        $title = $this->makeTitle('别人的抬头', userId: $other->id);

        Sanctum::actingAs($this->user);

        $this->putJson('/api/user/invoice-titles/'.$title->getKey(), [
            'type' => InvoiceTitleType::Personal->value,
            'name' => '恶意修改',
        ])->assertForbidden();

        $this->assertDatabaseHas('invoice_titles', [
            'id' => $title->getKey(),
            'title' => '别人的抬头',
        ]);
    }

    // ─── DELETE /api/user/invoice-titles/{id} ────────────────────

    public function test_can_delete_own_invoice_title(): void
    {
        $title = $this->makeTitle('待删除抬头');

        Sanctum::actingAs($this->user);

        $this->deleteJson('/api/user/invoice-titles/'.$title->getKey())
            ->assertNoContent();

        $this->assertSoftDeleted('invoice_titles', [
            'id' => $title->getKey(),
        ]);
    }

    public function test_cannot_delete_another_users_invoice_title(): void
    {
        $other = User::factory()->create();
        $title = $this->makeTitle('别人的抬头', userId: $other->id);

        Sanctum::actingAs($this->user);

        $this->deleteJson('/api/user/invoice-titles/'.$title->getKey())
            ->assertForbidden();

        $this->assertDatabaseHas('invoice_titles', [
            'id' => $title->getKey(),
        ]);
    }

    // ─── PUT /api/user/invoice-titles/{id}/default ───────────────

    public function test_set_default_exclusive_among_titles(): void
    {
        $first = $this->makeTitle('默认一', isDefault: true);
        $second = $this->makeTitle('默认二');

        Sanctum::actingAs($this->user);

        $this->putJson('/api/user/invoice-titles/'.$second->getKey().'/default')
            ->assertNoContent();

        $this->assertDatabaseHas('invoice_titles', [
            'id' => $second->getKey(),
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('invoice_titles', [
            'id' => $first->getKey(),
            'is_default' => false,
        ]);
    }

    // ─── POST /api/user/invoices/applications ────────────────────

    public function test_can_apply_invoice_for_paid_order(): void
    {
        $title = $this->makeTitle('开票抬头');
        $order = $this->makeOrder(amount: '200.00');

        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/invoices/applications', [
            'invoice_title_id' => $title->getKey(),
            'reason' => '公司报销',
            'order_ids' => [$order->getKey()],
        ])->assertCreated()
            ->assertJsonPath('reason', '公司报销');

        $applicationId = (int) $this->getJson('/api/user/invoices/applications')->json('list.0.application_id');

        $this->getJson('/api/user/invoices/applications/'.$applicationId)
            ->assertOk()
            ->assertJsonPath('reason', '公司报销')
            ->assertJsonPath('invoice_title.name', '开票抬头');
    }

    public function test_apply_invoice_requires_title_and_reason(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/invoices/applications', [])
            ->assertUnprocessable()
            // BaseFormRequest 首错即停，只返回第一个错误字段
            ->assertJsonValidationErrors('invoice_title_id');
    }

    public function test_apply_invoice_rejects_order_of_another_user(): void
    {
        $title = $this->makeTitle('开票抬头');
        $other = User::factory()->create();
        $order = $this->makeOrder(userId: $other->id);

        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/invoices/applications', [
            'invoice_title_id' => $title->getKey(),
            'reason' => '越权开票',
            'order_ids' => [$order->getKey()],
        ])->assertStatus(500);

        $this->assertDatabaseCount('invoice_applications', 0);
    }

    public function test_apply_invoice_rejects_canceled_order(): void
    {
        $title = $this->makeTitle('开票抬头');
        $order = $this->makeOrder(status: OrderStatus::Canceled);

        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/invoices/applications', [
            'invoice_title_id' => $title->getKey(),
            'reason' => '取消订单开票',
            'order_ids' => [$order->getKey()],
        ])->assertStatus(500);
    }

    public function test_apply_invoice_rejects_order_already_applied(): void
    {
        $title = $this->makeTitle('开票抬头');
        $order = $this->makeOrder();

        Sanctum::actingAs($this->user);

        $payload = [
            'invoice_title_id' => $title->getKey(),
            'reason' => '首次申请',
            'order_ids' => [$order->getKey()],
        ];

        $this->postJson('/api/user/invoices/applications', $payload)->assertCreated();

        $this->postJson('/api/user/invoices/applications', [
            ...$payload,
            'reason' => '重复申请',
        ])->assertStatus(500);
    }

    // ─── GET /api/user/invoices/applications ─────────────────────

    public function test_can_list_own_invoice_applications(): void
    {
        $title = $this->makeTitle('开票抬头');
        $order = $this->makeOrder();

        Sanctum::actingAs($this->user);

        $this->postJson('/api/user/invoices/applications', [
            'invoice_title_id' => $title->getKey(),
            'reason' => '报销',
            'order_ids' => [$order->getKey()],
        ])->assertCreated();

        $this->getJson('/api/user/invoices/applications')
            ->assertOk()
            ->assertJsonStructure(['list', 'page' => ['current', 'total']]);

        $this->assertSame(1, $this->getJson('/api/user/invoices/applications')->json('page.total'));
    }

    public function test_cannot_view_another_users_invoice_application(): void
    {
        $title = $this->makeTitle('开票抬头');
        $order = $this->makeOrder();

        Sanctum::actingAs($this->user);

        $id = (int) $this->postJson('/api/user/invoices/applications', [
            'invoice_title_id' => $title->getKey(),
            'reason' => '报销',
            'order_ids' => [$order->getKey()],
        ])->assertCreated()->json('application_id');

        $intruder = User::factory()->create();
        Sanctum::actingAs($intruder);

        $this->getJson('/api/user/invoices/applications/'.$id)
            ->assertForbidden();
    }

    // ─── GET /api/user/invoices/* ────────────────────────────────

    public function test_invoicable_orders_and_stats_are_available(): void
    {
        $title = $this->makeTitle('开票抬头');
        $paid = $this->makeOrder(status: OrderStatus::Paid);
        $this->makeOrder(status: OrderStatus::Pending);

        Sanctum::actingAs($this->user);

        // 未支付订单不出现在可开票列表
        $this->getJson('/api/user/invoices/orders')
            ->assertOk()
            ->assertJsonPath('page.total', 1);

        $this->postJson('/api/user/invoices/applications', [
            'invoice_title_id' => $title->getKey(),
            'reason' => '报销',
            'order_ids' => [$paid->getKey()],
        ])->assertCreated();

        $this->getJson('/api/user/invoices/stats')
            ->assertOk()
            ->assertJsonPath('title_count', 1)
            ->assertJsonPath('pending_count', 1)
            ->assertJsonPath('total_invoice', 0);
    }

    // ─── 未登录 ──────────────────────────────────────────────────

    public function test_guest_cannot_access_invoice_endpoints(): void
    {
        $this->getJson('/api/user/invoice-titles')->assertUnauthorized();
        $this->postJson('/api/user/invoice-titles', [])->assertUnauthorized();
        $this->getJson('/api/user/invoices/applications')->assertUnauthorized();
        $this->postJson('/api/user/invoices/applications', [])->assertUnauthorized();
        $this->getJson('/api/user/invoices/stats')->assertUnauthorized();
    }

    // ─── 测试夹具 ────────────────────────────────────────────────

    private function makeTitle(string $title, bool $isDefault = false, ?int $userId = null): InvoiceTitle
    {
        return InvoiceTitle::create([
            'user_id' => $userId ?? $this->user->id,
            'tenant_id' => $this->tenant->id,
            'type' => InvoiceTitleType::Personal,
            'title' => $title,
            'is_default' => $isDefault,
        ]);
    }

    private function makeOrder(
        OrderStatus $status = OrderStatus::Paid,
        string $amount = '100.00',
        ?int $userId = null
    ): Order {
        $product = Product::factory()->for($this->tenant, 'tenant')->create([
            'fulfillment_type' => [FulfillmentType::Mail->value],
        ]);
        $sku = Sku::factory()->create([
            'product_id' => $product->id,
            'price' => $amount,
            'stock' => 100,
        ]);

        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $userId ?? $this->user->id,
            'amount' => $amount,
            'freight' => '0.00',
            'coupon_discount' => '0.00',
            'status' => $status,
            'fulfillment_type' => FulfillmentType::Mail,
        ]);

        $order->items()->create([
            'orderable_type' => $sku->getMorphClass(),
            'orderable_id' => $sku->getKey(),
            'orderable_name' => '测试商品',
            'qty' => 1,
            'price' => $amount,
        ]);

        return $order->load('items');
    }
}
