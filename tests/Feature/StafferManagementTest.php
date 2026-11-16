<?php

namespace Tests\Feature;

use App\Enums\System\AdminType;
use App\Filament\Tenant\Clusters\Setting\Resources\Staffers\Pages\ManageStaffers;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use App\Policies\System\AdministratorPolicy;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\GrantsPermissions;
use Tests\TestCase;

/**
 * 租户端员工创建口径
 */
class StafferManagementTest extends TestCase
{
    use GrantsPermissions,
        RefreshDatabase;

    public function test_tenant_created_staffer_is_tenant_type_and_bound_to_current_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = Administrator::factory()->tenantAdmin()->create();
        $admin->tenants()->attach($tenant);

        $this->actingAs($admin, 'tenant');
        Filament::setCurrentPanel('tenant');
        Filament::setTenant($tenant, isQuiet: true);
        $this->grantPolicyPermissions($admin, [AdministratorPolicy::class]);

        Livewire::test(ManageStaffers::class)
            ->callAction('create', data: [
                'username' => 'staffer-01',
                'password' => 'secret123',
                'name' => '员工一',
                // 前端被篡改也应强制写成租户类型
                'type' => AdminType::Admin->value,
            ])
            ->assertHasNoActionErrors();

        $staffer = Administrator::where('username', 'staffer-01')->firstOrFail();

        $this->assertSame(AdminType::Tenant, $staffer->type);
        $this->assertTrue($staffer->tenants()->whereKey($tenant)->exists());
        $this->assertTrue($staffer->canAccessPanel(Filament::getPanel('tenant')));
        $this->assertFalse($staffer->canAccessPanel(Filament::getPanel('backend')));
    }
}
