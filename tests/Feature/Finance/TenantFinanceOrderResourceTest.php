<?php

namespace Tests\Feature\Finance;

use App\Enums\System\AvailableModule;
use App\Filament\Tenant\Clusters\Finance\Resources\RechargeOrders\Pages\ManageRechargeOrders;
use App\Filament\Tenant\Clusters\Finance\Resources\RechargeOrders\RechargeOrderResource;
use App\Filament\Tenant\Clusters\Finance\Resources\WithdrawOrders\Pages\ManageWithdrawOrders;
use App\Filament\Tenant\Clusters\Finance\Resources\WithdrawOrders\Pages\ViewWithdrawOrder;
use App\Filament\Tenant\Clusters\Finance\Resources\WithdrawOrders\WithdrawOrderResource;
use App\Models\Finance\RechargeOrder;
use App\Models\Finance\WithdrawOrder;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use App\Policies\Finance\RechargeOrderPolicy;
use App\Policies\Finance\WithdrawOrderPolicy;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\GrantsPermissions;
use Tests\TestCase;

/**
 * 租户端充值/提现只读列表按租户隔离
 */
class TenantFinanceOrderResourceTest extends TestCase
{
    use GrantsPermissions,
        RefreshDatabase;

    private Tenant $tenant;

    private Administrator $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'config' => ['modules' => [AvailableModule::Finance->value]],
        ]);

        $this->administrator = Administrator::factory()->tenantAdmin()->create();
        $this->administrator->tenants()->attach($this->tenant);

        $this->actingAs($this->administrator, 'tenant');
        Filament::setCurrentPanel('tenant');
        Filament::setTenant($this->tenant, isQuiet: true);
        $this->grantPolicyPermissions($this->administrator, [
            RechargeOrderPolicy::class,
            WithdrawOrderPolicy::class,
        ]);
    }

    public function test_recharge_list_only_shows_current_tenant(): void
    {
        $mine = RechargeOrder::factory()->create(['tenant_id' => $this->tenant->getKey()]);
        $other = RechargeOrder::factory()->create();

        // 必须在创建测试数据后再 boot：boot 会注册 Filament 的创建观察者，
        // 把当前租户自动关联到新记录，导致无法构造跨租户数据
        Filament::bootCurrentPanel();

        Livewire::test(ManageRechargeOrders::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other])
            ->assertTableColumnHidden('tenant.name')
            ->assertTableFilterHidden('tenant_id');
    }

    public function test_withdraw_list_only_shows_current_tenant(): void
    {
        $mine = WithdrawOrder::factory()->create(['tenant_id' => $this->tenant->getKey()]);
        $other = WithdrawOrder::factory()->create(['tenant_id' => Tenant::factory()->create()->getKey()]);

        Filament::bootCurrentPanel();

        Livewire::test(ManageWithdrawOrders::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other])
            ->assertTableColumnHidden('tenant.name')
            ->assertTableFilterHidden('tenant_id');
    }

    public function test_tenant_can_view_own_withdraw_order_but_not_another_tenant(): void
    {
        $mine = WithdrawOrder::factory()->create(['tenant_id' => $this->tenant->getKey()]);
        $other = WithdrawOrder::factory()->create(['tenant_id' => Tenant::factory()->create()->getKey()]);

        Filament::bootCurrentPanel();

        Livewire::test(ViewWithdrawOrder::class, ['record' => $mine->getKey()])
            ->assertOk()
            ->assertSee($mine->no);

        $this->get(WithdrawOrderResource::getUrl('view', ['record' => $other->getKey()]))
            ->assertNotFound();
    }

    public function test_staff_without_permission_cannot_access_recharge_or_withdraw_resources(): void
    {
        $staff = Administrator::factory()->tenantAdmin()->create();
        $staff->tenants()->attach($this->tenant);

        $this->actingAs($staff, 'tenant');

        $this->assertFalse(RechargeOrderResource::canAccess());
        $this->assertFalse(WithdrawOrderResource::canAccess());
    }
}
