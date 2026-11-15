<?php

namespace Tests\Feature;

use App\Enums\System\AvailableModule;
use App\Filament\Tenant\Clusters\Mall\Resources\Orders\OrderResource;
use App\Filament\Tenant\Clusters\Mall\Resources\Refunds\RefundResource;
use App\Models\Mall\StoreConfigure;
use App\Models\System\Administrator;
use App\Models\System\Tenant;
use App\Policies\System\ApiLogPolicy;
use App\Policies\System\ScheduleRunLogPolicy;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * 租户面板资源准入与系统保护
 */
class TenantResourceAccessTest extends TestCase
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

        Filament::setCurrentPanel('tenant');
        Filament::setTenant($this->tenant, isQuiet: true);
    }

    public function test_staff_without_permissions_cannot_access_mall_resources(): void
    {
        // 显式避开主键 1（当前 isAdministrator() 仍把 id=1 当超管，SYS-01）
        $staff = Administrator::factory()->create(['id' => 2]);
        $staff->tenants()->attach($this->tenant);
        $this->actingAs($staff, 'tenant');

        $this->assertFalse(OrderResource::canAccess());
        $this->assertFalse(RefundResource::canAccess());
    }

    public function test_platform_administrator_can_access_mall_resources(): void
    {
        $admin = Administrator::factory()->superAdmin()->create();
        $this->actingAs($admin, 'tenant');

        $this->assertTrue(OrderResource::canAccess());
        $this->assertTrue(RefundResource::canAccess());
    }

    public function test_audit_logs_cannot_be_deleted(): void
    {
        $admin = Administrator::factory()->superAdmin()->create();

        $this->assertFalse((new ScheduleRunLogPolicy)->delete($admin));
        $this->assertFalse((new ScheduleRunLogPolicy)->deleteAny($admin));
        $this->assertFalse((new ApiLogPolicy)->delete($admin));
        $this->assertFalse((new ApiLogPolicy)->deleteAny($admin));
    }

    public function test_super_administrator_cannot_be_force_deleted(): void
    {
        $admin = Administrator::factory()->superAdmin()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('超级管理员禁止永久删除');

        $admin->forceDelete();
    }
}
