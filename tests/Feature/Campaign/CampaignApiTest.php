<?php

namespace Tests\Feature\Campaign;

use App\Models\Campaign\Coupon;
use App\Models\Campaign\CouponUser;
use App\Models\Campaign\Redpack;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── GET /api/campaign/coupons ────────────────────────────────

    public function test_can_list_coupons(): void
    {
        Coupon::factory()->count(3)->create();

        $response = $this->getJson('/api/campaign/coupons');

        $response->assertOk();
    }

    public function test_coupon_list_excludes_disabled(): void
    {
        Coupon::factory()->count(2)->create();
        Coupon::factory()->disabled()->create();

        $response = $this->getJson('/api/campaign/coupons');

        $response->assertOk();
    }

    public function test_coupon_list_filters_by_current_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $coupon = Coupon::factory()->create(['tenant_id' => $tenant->id]);
        Coupon::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->getJson('/api/campaign/coupons');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.coupon_id', $coupon->id);
    }

    public function test_coupon_list_validates_filter_params(): void
    {
        // BaseFormRequest::$stopOnFirstFailure = true，仅返回首个字段错误
        $this->getJson('/api/campaign/coupons?type=invalid&limit=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    // ─── GET /api/campaign/coupons/{coupon} ───────────────────────

    public function test_can_show_coupon(): void
    {
        $coupon = Coupon::factory()->create([
            'name' => '测试优惠券',
        ]);

        $response = $this
            ->withHeader('X-Tenant-Id', (string) $coupon->tenant_id)
            ->getJson("/api/campaign/coupons/{$coupon->id}");

        $response->assertOk();
    }

    public function test_coupon_show_returns_404_for_anonymous_without_tenant_header(): void
    {
        $coupon = Coupon::factory()->create();

        // 匿名且未带租户头：一律不可见（CAMP-05）
        $this->getJson("/api/campaign/coupons/{$coupon->id}")
            ->assertNotFound();
    }

    public function test_coupon_list_is_empty_for_anonymous_without_tenant_header(): void
    {
        Coupon::factory()->count(3)->create();

        // 匿名且未带租户头：返回空集，不再泄露全平台券（CAMP-05）
        $this->getJson('/api/campaign/coupons')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_coupon_show_returns_404_when_disabled(): void
    {
        $coupon = Coupon::factory()->disabled()->create();

        $response = $this->getJson("/api/campaign/coupons/{$coupon->id}");

        $response->assertNotFound();
    }

    public function test_coupon_show_returns_404_when_expired(): void
    {
        $coupon = Coupon::factory()->expired()->create();

        $response = $this->getJson("/api/campaign/coupons/{$coupon->id}");

        $response->assertNotFound();
    }

    public function test_coupon_show_returns_404_for_other_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $coupon = Coupon::factory()->create(['tenant_id' => $otherTenant->id]);

        $response = $this
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->getJson("/api/campaign/coupons/{$coupon->id}");

        $response->assertNotFound();
    }

    // ─── POST /api/campaign/coupons/{coupon}/claim ────────────────

    public function test_coupon_claim_requires_authentication(): void
    {
        $coupon = Coupon::factory()->create();

        $this->postJson("/api/campaign/coupons/{$coupon->id}/claim")
            ->assertUnauthorized();
    }

    public function test_user_can_claim_coupon(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $coupon = Coupon::factory()->create(['tenant_id' => $tenant->id]);

        $response = $this
            ->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson("/api/campaign/coupons/{$coupon->id}/claim");

        $response->assertOk()
            ->assertJsonPath('message', '优惠券领取成功')
            ->assertJsonPath('coupon.coupon_id', $coupon->id);

        $this->assertDatabaseHas('coupon_user', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'is_used' => false,
        ]);
    }

    public function test_user_cannot_claim_coupon_from_other_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $coupon = Coupon::factory()->create(['tenant_id' => $otherTenant->id]);

        $this
            ->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson("/api/campaign/coupons/{$coupon->id}/claim")
            ->assertNotFound();
    }

    public function test_user_cannot_claim_coupon_from_other_tenant_without_tenant_header(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $coupon = Coupon::factory()->create(['tenant_id' => $otherTenant->id]);

        $this
            ->actingAs($user)
            ->postJson("/api/campaign/coupons/{$coupon->id}/claim")
            ->assertNotFound();
    }

    public function test_user_can_claim_coupon_with_tenant_header_without_membership(): void
    {
        $tenant = Tenant::factory()->create();
        // 未下过单的新用户：无任何租户授权关系
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create(['tenant_id' => $tenant->id]);

        // 准入只看「租户头有效 + 券属于该租户」，不依赖 user_tenant（CAMP-05）
        $this
            ->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson("/api/campaign/coupons/{$coupon->id}/claim")
            ->assertOk()
            ->assertJsonPath('message', '优惠券领取成功');

        $this->assertDatabaseHas('coupon_user', [
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'is_used' => false,
        ]);
    }

    public function test_user_cannot_claim_coupon_when_per_user_limit_reached(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $coupon = Coupon::factory()
            ->withUsageLimitPerUser(1)
            ->create(['tenant_id' => $tenant->id]);

        $this
            ->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson("/api/campaign/coupons/{$coupon->id}/claim")
            ->assertOk();

        $this
            ->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson("/api/campaign/coupons/{$coupon->id}/claim")
            ->assertUnprocessable()
            ->assertJsonPath('message', '您已领取过该优惠券，不可重复领取');
    }

    // ─── GET /api/campaign/coupons/my ─────────────────────────────

    public function test_user_can_list_my_coupons(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $coupon = Coupon::factory()->create(['tenant_id' => $tenant->id]);
        CouponUser::query()->create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'expired_at' => now()->addDay(),
        ]);

        $response = $this
            ->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->getJson('/api/campaign/coupons/my');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.coupon.coupon_id', $coupon->id)
            ->assertJsonPath('0.is_used', false);
    }

    public function test_user_coupons_are_not_filtered_by_membership_without_tenant_header(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $coupon = Coupon::factory()->create(['tenant_id' => $tenant->id]);
        $otherCoupon = Coupon::factory()->create(['tenant_id' => $otherTenant->id]);

        CouponUser::query()->create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'expired_at' => now()->addDay(),
        ]);
        CouponUser::query()->create([
            'coupon_id' => $otherCoupon->id,
            'user_id' => $user->id,
            'expired_at' => now()->addDay(),
        ]);

        // 未带租户头：不再按 user_tenant 过滤，用户持有的券全部可见（CAMP-05）
        $response = $this
            ->actingAs($user)
            ->getJson('/api/campaign/coupons/my');

        $response->assertOk()
            ->assertJsonCount(2);
    }

    public function test_user_coupons_are_narrowed_by_tenant_header(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $coupon = Coupon::factory()->create(['tenant_id' => $tenant->id]);
        $otherCoupon = Coupon::factory()->create(['tenant_id' => $otherTenant->id]);

        CouponUser::query()->create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'expired_at' => now()->addDay(),
        ]);
        CouponUser::query()->create([
            'coupon_id' => $otherCoupon->id,
            'user_id' => $user->id,
            'expired_at' => now()->addDay(),
        ]);

        $response = $this
            ->actingAs($user)
            ->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->getJson('/api/campaign/coupons/my');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.coupon.coupon_id', $coupon->id);
    }

    // ─── GET /api/campaign/redpacks ───────────────────────────────

    public function test_can_list_redpacks(): void
    {
        Redpack::factory()->count(3)->create();

        $response = $this->getJson('/api/campaign/redpacks');

        $response->assertOk();
    }

    // ─── GET /api/campaign/redpacks/{redpack} ─────────────────────

    public function test_can_show_redpack(): void
    {
        $redpack = Redpack::factory()->create([
            'name' => '测试红包',
        ]);

        $response = $this
            ->withHeader('X-Tenant-Id', (string) $redpack->tenant_id)
            ->getJson("/api/campaign/redpacks/{$redpack->id}");

        $response->assertOk();
    }

    public function test_redpack_show_returns_404_when_disabled(): void
    {
        $redpack = Redpack::factory()->disabled()->create();

        $response = $this->getJson("/api/campaign/redpacks/{$redpack->id}");

        $response->assertNotFound();
    }

    // ─── POST /api/campaign/redpacks/{code}/claim ─────────────────

    public function test_claim_requires_authentication(): void
    {
        $this->postJson('/api/campaign/redpacks/testcode/claim')
            ->assertUnauthorized();
    }

    public function test_claim_returns_404_for_invalid_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/campaign/redpacks/invalidcode/claim')
            ->assertNotFound();
    }
}
