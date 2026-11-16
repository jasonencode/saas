<?php

namespace Tests\Feature;

use App\Filament\Backend\Clusters\Mall\Resources\Expresses\ExpressResource;
use App\Models\System\Administrator;
use App\Policies\Mall\ExpressPolicy;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsPermissions;
use Tests\TestCase;

/**
 * 运营端资源准入回归
 *
 * 资源不得用 `canAccess()` 覆盖策略判定（同 SYS-02 / BE-02）。
 */
class BackendResourceAccessTest extends TestCase
{
    use GrantsPermissions;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('backend');
    }

    public function test_express_resource_requires_policy_permission(): void
    {
        $admin = Administrator::factory()->create(['id' => 2]);
        $this->actingAs($admin, 'backend');

        $this->assertFalse(ExpressResource::canAccess());
    }

    public function test_express_resource_is_accessible_with_permission(): void
    {
        $admin = Administrator::factory()->create(['id' => 3]);
        $this->actingAs($admin, 'backend');
        $this->grantPolicyPermissions($admin, [ExpressPolicy::class]);

        $this->assertTrue(ExpressResource::canAccess());
    }

    public function test_platform_administrator_can_access_express_resource(): void
    {
        $this->actingAs(Administrator::factory()->superAdmin()->create(), 'backend');

        $this->assertTrue(ExpressResource::canAccess());
    }
}
