<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Plan;
use App\Models\System\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 计划标识唯一性：仅在同一租户内唯一
 */
class PlanAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_alias_is_allowed_across_tenants(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->makePlan($tenantA, 'basic');
        $this->makePlan($tenantB, 'basic');

        $this->assertDatabaseCount('plans', 2);
    }

    public function test_same_alias_is_rejected_within_the_same_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $this->makePlan($tenant, 'basic');

        $this->expectException(QueryException::class);

        $this->makePlan($tenant, 'basic');
    }

    private function makePlan(Tenant $tenant, string $alias): Plan
    {
        return Plan::create([
            'tenant_id' => $tenant->getKey(),
            'name' => $alias.' 计划',
            'alias' => $alias,
            'status' => true,
            'sort' => 0,
        ]);
    }
}
