<?php

namespace Tests\Feature;

use App\Filament\Backend\Clusters\Setting\Resources\Roles\Pages\EditRole;
use App\Models\System\Administrator;
use App\Models\System\AdminRole;
use App\Policies\System\ApiLogPolicy;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 角色权限编辑：系统角色/自身角色保护、事务落库与审计
 */
class RolePermissionEditTest extends TestCase
{
    use RefreshDatabase;

    private Administrator $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Administrator::factory()->superAdmin()->create();
        $this->actingAs($this->admin, 'backend');
        Filament::setCurrentPanel('backend');
    }

    public function test_system_role_cannot_be_edited(): void
    {
        $role = AdminRole::create(['name' => '系统角色', 'is_sys' => true]);

        $this->get(EditRole::getUrl(['record' => $role]))->assertForbidden();
    }

    public function test_own_role_cannot_be_edited(): void
    {
        $role = AdminRole::create(['name' => '我的角色', 'is_sys' => false]);
        $this->admin->roles()->attach($role);

        $this->get(EditRole::getUrl(['record' => $role]))->assertForbidden();
    }

    public function test_permission_update_persists_and_writes_audit_log(): void
    {
        $role = AdminRole::create(['name' => '客服', 'is_sys' => false]);

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->fillForm([
                'name' => '客服',
                'permissions' => [ApiLogPolicy::class => ['viewAny']],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('admin_role_permissions', [
            'role_id' => $role->getKey(),
            'policy' => ApiLogPolicy::class,
            'method' => 'viewAny',
        ]);

        $this->assertDatabaseHas('event_logs', [
            'event' => 'system.role_permissions_updated',
            'subject_id' => $role->getKey(),
        ]);
    }
}
