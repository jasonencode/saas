<?php

namespace Tests\Feature;

use App\Enums\Mall\OrderStatus;
use App\Enums\System\AvailableModule;
use App\Filament\Tenant\Clusters\Mall\Resources\Orders\Pages\ManageOrders;
use App\Filament\Tenant\Clusters\Mall\Resources\Orders\Pages\ViewOrder;
use App\Filament\Tenant\Clusters\Mall\Resources\Products\Pages\ViewProduct;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\Product;
use App\Models\Mall\Sku;
use App\Models\Mall\StoreConfigure;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use App\Models\User\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 租户端核心资源渲染冒烟（TST-01 残余）：订单详情/订单列表/商品详情
 */
class TenantMallResourceRenderTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'config' => ['modules' => [AvailableModule::Mall->value]],
        ]);

        StoreConfigure::create([
            'tenant_id' => $this->tenant->getKey(),
            'store_name' => '测试店铺',
            'enabled' => true,
        ]);

        // 平台超管（主键 1）绕过策略；租户员工需先分配权限，渲染用例从简
        $administrator = Administrator::factory()->superAdmin()->create();
        $administrator->tenants()->attach($this->tenant);

        $this->actingAs($administrator, 'tenant');
        Filament::setCurrentPanel('tenant');
        Filament::setTenant($this->tenant, isQuiet: true);
    }

    public function test_view_order_page_renders(): void
    {
        $order = $this->createOrder();

        Livewire::test(ViewOrder::class, ['record' => $order->no])
            ->assertSuccessful()
            ->assertSee($order->no);
    }

    public function test_manage_orders_page_renders(): void
    {
        $order = $this->createOrder();

        Livewire::test(ManageOrders::class)
            ->assertSuccessful()
            ->assertSee($order->no);
    }

    public function test_view_product_page_renders(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->getKey()]);
        Sku::factory()->create(['product_id' => $product->getKey()]);

        Livewire::test(ViewProduct::class, ['record' => $product->getKey()])
            ->assertSuccessful()
            ->assertSee($product->name);
    }

    private function createOrder(): Order
    {
        $user = User::factory()->create();
        $user->tenants()->attach($this->tenant);

        $product = Product::factory()->create(['tenant_id' => $this->tenant->getKey()]);
        $sku = Sku::factory()->create([
            'product_id' => $product->getKey(),
            'price' => '100.00',
            'stock' => 10,
        ]);

        $order = Order::create([
            'tenant_id' => $this->tenant->getKey(),
            'user_id' => $user->getKey(),
            'status' => OrderStatus::Paid,
            'fulfillment_type' => 'mail',
            'amount' => '100.00',
            'freight' => '0.00',
        ]);

        OrderItem::create([
            'order_id' => $order->getKey(),
            'orderable_type' => $sku->getMorphClass(),
            'orderable_id' => $sku->getKey(),
            'qty' => 1,
            'price' => '100.00',
        ]);

        return $order->load('items');
    }
}
