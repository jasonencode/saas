<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentStatus;
use App\Enums\System\AvailableModule;
use App\Filament\Tenant\Clusters\Finance\Resources\Payments\Pages\ViewPayment;
use App\Models\Finance\PaymentOrder;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use App\Models\User\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_view_page_shows_payment_information(): void
    {
        $tenant = Tenant::factory()->create([
            'config' => ['modules' => [AvailableModule::Finance->value]],
        ]);

        $payment = PaymentOrder::create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => User::factory()->create()->getKey(),
            'gateway' => PaymentGateway::Manual,
            'amount' => 88.50,
            'expired_at' => now()->addHour(),
        ]);

        $payment->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $administrator = Administrator::factory()->tenantAdmin()->create();
        $administrator->tenants()->attach($tenant);

        $this->actingAs($administrator, 'tenant');
        Filament::setCurrentPanel('tenant');
        Filament::setTenant($tenant, isQuiet: true);
        Gate::before(fn (): bool => true);

        Livewire::test(ViewPayment::class, ['record' => $payment->getKey()])
            ->assertOk()
            ->assertSee($payment->no)
            ->assertSee('支付金额')
            ->assertSee('88.50');
    }
}
