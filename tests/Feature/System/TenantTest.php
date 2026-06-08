<?php

namespace Tests\Feature\System;

use App\Models\System\Administrator;
use App\Models\System\AdminRole;
use App\Models\System\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_a_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => '演示组织',
            'slug' => 'demo-org',
            'status' => true,
        ]);

        $this->assertModelExists($tenant);
        $this->assertSame('演示组织', $tenant->name);
        $this->assertSame('demo-org', $tenant->slug);
        $this->assertTrue($tenant->status);
    }

    public function test_tenant_creation_triggers_auto_make_permissions(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertDatabaseHas('admin_roles', [
            'tenant_id' => $tenant->id,
            'is_sys' => true,
        ]);

        $role = AdminRole::where('tenant_id', $tenant->id)
            ->where('is_sys', true)
            ->first();

        $this->assertNotNull($role);
        $this->assertStringContainsString('Admin', $role->name);
        $this->assertStringContainsString('不可删除', $role->description);

        // 应该创建了权限记录
        $this->assertGreaterThan(0, $role->permissions()->count());
    }

    public function test_tenant_has_many_administrators(): void
    {
        $tenant = Tenant::factory()->create();
        $admins = Administrator::factory()->count(3)->create();

        $tenant->administrators()->attach($admins);

        $this->assertCount(3, $tenant->administrators);
    }

    public function test_tenant_has_many_roles(): void
    {
        $tenant = Tenant::factory()->create();
        // Tenant creation auto-creates 1 system role via autoMakePermissions
        AdminRole::factory()->count(2)->create([
            'tenant_id' => $tenant->id,
        ]);

        // 1 system role (auto-created) + 2 factory roles = 3
        $this->assertCount(3, $tenant->roles()->get());
    }

    public function test_tenant_has_many_users(): void
    {
        $tenant = Tenant::factory()->create();

        // Create user directly via DB to avoid event complexity
        DB::table('users')->insert([
            'tenant_id' => $tenant->id,
            'username' => 'testuser',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertCount(1, $tenant->users);
    }

    public function test_tenant_can_toggle_status(): void
    {
        $tenant = Tenant::factory()->create([
            'status' => true,
        ]);

        $this->assertTrue($tenant->isEnabled());

        $tenant->disable();
        $tenant->refresh();
        $this->assertFalse($tenant->status);
        $this->assertTrue($tenant->isDisabled());

        $tenant->enable();
        $tenant->refresh();
        $this->assertTrue($tenant->status);
    }

    public function test_tenant_default_avatar(): void
    {
        $tenant = Tenant::factory()->create([
            'avatar' => null,
        ]);

        $this->assertSame('/images/avatar.jpg', $tenant->getFilamentAvatarUrl());
    }

    public function test_tenant_with_expired_date(): void
    {
        $tenant = Tenant::factory()->expired()->create();

        $this->assertNotNull($tenant->expired_at);
        $this->assertTrue($tenant->expired_at->isPast());
    }

    public function test_of_tenant_scope(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Tenant creation auto-creates 1 system role each
        AdminRole::factory()->create(['tenant_id' => $tenant1->id]);
        AdminRole::factory()->create(['tenant_id' => $tenant2->id]);

        $rolesForTenant1 = AdminRole::ofTenant($tenant1)->get();

        // 1 system role (auto-created) + 1 factory role = 2 for tenant1
        $this->assertCount(2, $rolesForTenant1);
        $this->assertSame($tenant1->id, $rolesForTenant1->first()->tenant_id);
    }
}
