<?php

namespace Tests\Feature\System;

use App\Models\System\Administrator;
use App\Models\System\AdminRole;
use App\Models\System\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_role(): void
    {
        $tenant = Tenant::factory()->create();
        $role = AdminRole::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => '编辑',
            'description' => '内容编辑角色',
        ]);

        $this->assertModelExists($role);
        $this->assertSame('编辑', $role->name);
        $this->assertSame('内容编辑角色', $role->description);
        $this->assertFalse($role->is_sys);
    }

    public function test_role_belongs_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $role = AdminRole::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->assertTrue($role->tenant()->is($tenant));
    }

    public function test_role_can_have_permissions(): void
    {
        $role = AdminRole::factory()->create();

        $permission = $role->permissions()->create([
            'policy' => 'App\Policies\Content\ContentPolicy',
            'method' => 'viewAny',
        ]);

        $this->assertTrue($role->permissions()->where('id', $permission->id)->exists());
    }

    public function test_role_can_have_administrators(): void
    {
        $role = AdminRole::factory()->create();
        $admin = Administrator::factory()->create();

        $role->administrators()->attach($admin);

        $this->assertTrue($role->administrators()->where('administrator_id', $admin->id)->exists());
    }

    public function test_system_role_cannot_be_deleted(): void
    {
        $role = AdminRole::factory()->systemRole()->create();

        $result = $role->delete();

        $this->assertFalse($result);
        $this->assertModelExists($role);
    }

    public function test_non_system_role_can_be_deleted(): void
    {
        $role = AdminRole::factory()->create();

        $role->delete();

        $this->assertSoftDeleted($role);
    }

    public function test_deleting_role_soft_deletes_role(): void
    {
        $role = AdminRole::factory()->create();
        $role->permissions()->create([
            'policy' => 'App\Policies\TestPolicy',
            'method' => 'viewAny',
        ]);

        $role->delete();

        $this->assertSoftDeleted($role);
    }
}
