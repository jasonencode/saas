<?php

namespace Tests\Feature\Campaign;

use App\Models\Campaign\Coupon;
use App\Models\Campaign\Redpack;
use App\Models\Campaign\RedpackCode;
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

    // ─── GET /api/campaign/coupons/{coupon} ───────────────────────

    public function test_can_show_coupon(): void
    {
        $coupon = Coupon::factory()->create([
            'name' => '测试优惠券',
        ]);

        $response = $this->getJson("/api/campaign/coupons/{$coupon->id}");

        $response->assertOk();
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

        $response = $this->getJson("/api/campaign/redpacks/{$redpack->id}");

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
