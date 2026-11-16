<?php

namespace Tests\Feature;

use App\Enums\Finance\InvoiceStatus;
use App\Enums\Finance\InvoiceType;
use App\Enums\Mall\OrderStatus;
use App\Filament\Backend\Clusters\Finance\Resources\Invoices\Pages\ManageInvoices;
use App\Filament\Backend\Clusters\Mall\Resources\Orders\Pages\ManageOrders;
use App\Models\Finance\Invoice;
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
 * 运营端订单/发票列表操作入口
 */
class BackendMallOpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_backend_order_table_exposes_service_actions(): void
    {
        $this->authenticateBackend();

        $paid = $this->createOrder(OrderStatus::Paid);
        $pending = $this->createOrder(OrderStatus::Pending);

        Livewire::test(ManageOrders::class)
            ->assertSuccessful()
            ->assertTableActionVisible('view', $paid)
            ->assertTableActionVisible('orderModifyAddress', $paid)
            ->assertTableActionVisible('orderShip', $paid)
            ->assertTableActionVisible('orderAddRemark', $paid)
            ->assertTableActionHidden('orderCancel', $paid)
            ->assertTableActionVisible('orderCancel', $pending)
            ->assertTableActionVisible('orderPayment', $pending);
    }

    public function test_backend_invoice_table_has_view_action(): void
    {
        $this->authenticateBackend();

        $invoice = Invoice::create([
            'user_id' => User::factory()->create()->getKey(),
            'tenant_id' => Tenant::factory()->create()->getKey(),
            'invoice_application_id' => 1,
            'invoice_no' => 'INV-0001',
            'invoice_date' => now()->toDateString(),
            'type' => InvoiceType::Normal->value,
            'amount' => '10.00',
            'status' => InvoiceStatus::Issued->value,
            'creator' => 'system',
        ]);

        Livewire::test(ManageInvoices::class)
            ->assertSuccessful()
            ->assertTableActionVisible('view', $invoice)
            ->assertSee($invoice->invoice_no);
    }

    private function authenticateBackend(): void
    {
        $this->actingAs(Administrator::factory()->superAdmin()->create(), 'backend');
        Filament::setCurrentPanel('backend');
    }

    private function createOrder(OrderStatus $status): Order
    {
        $tenant = Tenant::factory()->create();

        StoreConfigure::create([
            'tenant_id' => $tenant->getKey(),
            'store_name' => '测试店铺',
            'enabled' => true,
        ]);

        $product = Product::factory()->create(['tenant_id' => $tenant->getKey()]);
        $sku = Sku::factory()->create([
            'product_id' => $product->getKey(),
            'price' => '100.00',
            'stock' => 10,
        ]);

        $order = Order::create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => User::factory()->create()->getKey(),
            'status' => $status,
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
