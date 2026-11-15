<?php

namespace Tests\Feature;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentRefundStatus;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundExpressStatus;
use App\Enums\Mall\RefundReason;
use App\Enums\Mall\RefundStatus;
use App\Enums\Mall\RefundType;
use App\Enums\System\AvailableModule;
use App\Filament\Tenant\Clusters\Mall\Resources\Refunds\Pages\ViewRefund;
use App\Models\Finance\PaymentOrder;
use App\Models\Finance\PaymentRefund;
use App\Models\Mall\Express;
use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use App\Models\Mall\RefundExpress;
use App\Models\Mall\StoreConfigure;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use App\Models\User\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 租户端售后详情：支付退款与退货物流的展示
 */
class TenantRefundResourceTest extends TestCase
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

        $administrator = Administrator::factory()->tenantAdmin()->create();
        $administrator->tenants()->attach($this->tenant);

        $this->actingAs($administrator, 'tenant');
        Filament::setCurrentPanel('tenant');
        Filament::setTenant($this->tenant, isQuiet: true);
    }

    public function test_view_page_renders_payment_refund_and_return_express(): void
    {
        $refund = $this->createRefund();

        Livewire::test(ViewRefund::class, ['record' => $refund->no])
            ->assertSuccessful()
            ->assertSee('支付退款')
            ->assertSee('退货物流')
            ->assertSee('顺丰速运')
            ->assertSee('SF123456789')
            ->assertSee('通道退款单号');
    }

    private function createRefund(): Refund
    {
        $user = User::factory()->create();
        $user->tenants()->attach($this->tenant);

        $order = Order::create([
            'tenant_id' => $this->tenant->getKey(),
            'user_id' => $user->getKey(),
            'status' => OrderStatus::Signed,
            'fulfillment_type' => FulfillmentType::Mail,
            'amount' => '100.00',
            'freight' => '0.00',
        ]);

        $refund = Refund::create([
            'tenant_id' => $this->tenant->getKey(),
            'user_id' => $user->getKey(),
            'order_id' => $order->getKey(),
            'type' => RefundType::ReturnRefund,
            'reason' => RefundReason::NotWant,
            'total' => '100.00',
            'goods_amount' => '100.00',
            'freight_amount' => '0.00',
            'status' => RefundStatus::Processing,
        ]);

        $payment = PaymentOrder::create([
            'tenant_id' => $this->tenant->getKey(),
            'user_id' => $user->getKey(),
            'gateway' => PaymentGateway::Balance,
            'amount' => '100.00',
            'expired_at' => now()->addMinutes(30),
            'paymentable' => $order,
        ]);
        $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);

        PaymentRefund::create([
            'tenant_id' => $this->tenant->getKey(),
            'payment_order_id' => $payment->getKey(),
            'amount' => '100.00',
            'status' => PaymentRefundStatus::Processing,
            'source' => $refund,
        ]);

        $express = Express::factory()->create(['name' => '顺丰速运']);

        RefundExpress::create([
            'refund_id' => $refund->getKey(),
            'express_id' => $express->getKey(),
            'express_no' => 'SF123456789',
            'status' => RefundExpressStatus::Shipped,
            'shipped_at' => now(),
        ]);

        return $refund;
    }
}
