<?php

namespace Tests\Feature\User;

use App\Models\System\Tenant;
use App\Models\User\Identity;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IdentityApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create();
    }

    // ─── GET /api/user/identities ────────────────────────────────

    public function test_can_list_own_active_identities(): void
    {
        $identity = $this->makeIdentity(name: '会员');
        $this->attachToUser($identity);

        // user_identity 表对 (user_id, tenant_id) 有唯一约束，过期身份放到另一租户
        $otherTenant = Tenant::factory()->create();
        $expired = Identity::create([
            'tenant_id' => $otherTenant->id,
            'name' => '过期身份',
            'status' => true,
        ]);
        $this->attachToUser($expired, endAt: now()->subDay());

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/user/identities')
            ->assertOk();

        $names = collect($response->json())->pluck('name');

        $this->assertCount(1, $names);
        $this->assertContains('会员', $names);
    }

    public function test_identity_list_empty_when_user_has_none(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/identities')
            ->assertOk()
            ->assertJson([]);
    }

    // ─── GET /api/user/identities/available/{tenantId} ───────────

    public function test_available_lists_only_subscribable_identities_of_tenant(): void
    {
        $this->makeIdentity(name: '可订阅', canSubscribe: true);
        $this->makeIdentity(name: '不可订阅', canSubscribe: false);
        $this->makeIdentity(name: '已停用', canSubscribe: true, status: false);

        $otherTenant = Tenant::factory()->create();
        Identity::create([
            'tenant_id' => $otherTenant->id,
            'name' => '他店身份',
            'can_subscribe' => true,
            'status' => true,
        ]);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/identities/available/'.$this->tenant->id)
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', '可订阅');
    }

    public function test_available_returns_empty_for_tenant_without_identities(): void
    {
        $emptyTenant = Tenant::factory()->create();

        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/identities/available/'.$emptyTenant->id)
            ->assertOk()
            ->assertJson([]);
    }

    // ─── GET /api/user/identities/{identity}/check ───────────────

    public function test_check_returns_true_for_held_identity(): void
    {
        $identity = $this->makeIdentity();
        $this->attachToUser($identity, endAt: now()->addDays(30));

        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/identities/'.$identity->id.'/check')
            ->assertOk()
            ->assertJsonPath('has', true);
    }

    public function test_check_flags_expiring_soon(): void
    {
        $identity = $this->makeIdentity();
        $this->attachToUser($identity, endAt: now()->addDays(3));

        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/identities/'.$identity->id.'/check')
            ->assertOk()
            ->assertJsonPath('has', true)
            ->assertJsonPath('expiring_soon', true);
    }

    public function test_check_returns_false_for_expired_identity(): void
    {
        $identity = $this->makeIdentity();
        $this->attachToUser($identity, endAt: now()->subDay());

        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/identities/'.$identity->id.'/check')
            ->assertOk()
            ->assertJsonPath('has', false)
            ->assertJsonPath('expiring_soon', false);
    }

    public function test_check_returns_false_for_never_attached_identity(): void
    {
        $identity = $this->makeIdentity();

        Sanctum::actingAs($this->user);

        $this->getJson('/api/user/identities/'.$identity->id.'/check')
            ->assertOk()
            ->assertJsonPath('has', false);
    }

    // ─── 未登录 ──────────────────────────────────────────────────

    public function test_guest_cannot_access_identity_endpoints(): void
    {
        $identity = $this->makeIdentity();

        $this->getJson('/api/user/identities')->assertUnauthorized();
        $this->getJson('/api/user/identities/available/'.$this->tenant->id)->assertUnauthorized();
        $this->getJson('/api/user/identities/'.$identity->id.'/check')->assertUnauthorized();
    }

    // ─── 测试夹具 ────────────────────────────────────────────────

    private function makeIdentity(
        string $name = '测试身份',
        bool $canSubscribe = true,
        bool $status = true
    ): Identity {
        return Identity::create([
            'tenant_id' => $this->tenant->id,
            'name' => $name,
            'can_subscribe' => $canSubscribe,
            'status' => $status,
        ]);
    }

    private function attachToUser(Identity $identity, ?string $endAt = null): void
    {
        $this->user->identities()->attach($identity->id, [
            'tenant_id' => $identity->tenant_id,
            'start_at' => now(),
            'end_at' => $endAt,
        ]);
    }
}
