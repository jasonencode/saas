<?php

namespace Tests\Feature\System;

use App\Enums\System\AdminType;
use App\Models\System\Administrator;
use App\Models\System\AdminRole;
use App\Models\System\Tenant;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AdministratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_an_administrator(): void
    {
        $admin = Administrator::factory()->create([
            'username' => 'testadmin',
            'name' => 'Test Admin',
            'type' => AdminType::Admin,
        ]);

        $this->assertModelExists($admin);
        $this->assertSame('testadmin', $admin->username);
        $this->assertSame('Test Admin', $admin->name);
        $this->assertTrue($admin->type === AdminType::Admin);
    }

    public function test_it_can_assign_roles_to_administrator(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = Administrator::factory()->create();
        $role = AdminRole::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $admin->roles()->attach($role);

        $this->assertTrue($admin->roles()->where('role_id', $role->id)->exists());
    }

    public function test_it_can_assign_tenants_to_administrator(): void
    {
        $admin = Administrator::factory()->create();
        $tenant = Tenant::factory()->create();

        $admin->tenants()->attach($tenant);

        $this->assertTrue($admin->tenants()->where('tenant_id', $tenant->id)->exists());
    }

    public function test_it_prevents_deleting_super_administrator(): void
    {
        $admin = Administrator::factory()->superAdmin()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('超级管理员禁止删除');

        $admin->delete();
    }

    public function test_it_allows_deleting_non_super_administrator(): void
    {
        // Create a non-super admin (not the first record, so ID != 1)
        Administrator::factory()->create(); // This one gets ID=1 (super admin check passes anyway, but let's skip it)
        $admin = Administrator::factory()->create([
            'username' => 'deletable-admin',
        ]);

        $admin->delete();

        $this->assertSoftDeleted($admin);
    }

    public function test_super_administrator_can_access_backend_panel(): void
    {
        $admin = Administrator::factory()->superAdmin()->create();

        // 超级管理员没有租户关联时，可以访问 backend 面板
        $panel = new class('backend') extends Panel
        {
            public function getId(): string
            {
                return 'backend';
            }
        };

        $this->assertTrue($admin->canAccessPanel($panel));
    }

    public function test_administrator_with_tenants_cannot_access_backend_panel(): void
    {
        $admin = Administrator::factory()->create();
        $tenant = Tenant::factory()->create();

        $admin->tenants()->attach($tenant);

        $panel = new class('backend') extends Panel
        {
            public function getId(): string
            {
                return 'backend';
            }
        };

        $this->assertFalse($admin->canAccessPanel($panel));
    }

    public function test_gets_filament_name_from_name_attribute(): void
    {
        $admin = Administrator::factory()->create([
            'name' => '张管理员',
        ]);

        $this->assertSame('张管理员', $admin->getFilamentName());
    }

    public function test_returns_default_avatar_when_no_avatar_set(): void
    {
        $admin = Administrator::factory()->create([
            'avatar' => null,
        ]);

        $this->assertSame('/images/avatar.jpg', $admin->getFilamentAvatarUrl());
    }
}
