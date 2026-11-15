<?php

namespace Tests\Feature\Mall;

use App\Models\Mall\PickupPoint;
use App\Models\Mall\ReturnAddress;
use App\Models\System\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 公共商城接口的租户隔离（自提点 / 退货地址）
 */
class PublicTenantFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_pickup_points_are_limited_to_current_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $other = Tenant::factory()->create();

        PickupPoint::factory()->for($tenant, 'tenant')->create(['name' => '本店自提点']);
        PickupPoint::factory()->for($other, 'tenant')->create(['name' => '他店自提点']);

        $this->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->getJson('/api/mall/pickup-points')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', '本店自提点');
    }

    public function test_public_endpoints_return_empty_without_tenant_header(): void
    {
        $tenant = Tenant::factory()->create();
        PickupPoint::factory()->for($tenant, 'tenant')->create();
        ReturnAddress::factory()->for($tenant, 'tenant')->create();

        // 无租户上下文不返回任何门店/地址，避免跨租户 PII
        $this->getJson('/api/mall/pickup-points')
            ->assertOk()
            ->assertJsonCount(0);

        $this->getJson('/api/mall/return-address')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_return_addresses_are_limited_to_current_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $other = Tenant::factory()->create();

        ReturnAddress::factory()->for($tenant, 'tenant')->create(['name' => '本店退货地址']);
        ReturnAddress::factory()->for($other, 'tenant')->create(['name' => '他店退货地址']);

        $this->withHeader('X-Tenant-Id', (string) $tenant->getKey())
            ->getJson('/api/mall/return-address')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', '本店退货地址');
    }
}
