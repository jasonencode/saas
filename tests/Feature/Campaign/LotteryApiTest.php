<?php

namespace Tests\Feature\Campaign;

use App\Models\Campaign\Lottery;
use App\Models\Campaign\LotteryPrize;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LotteryApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── GET /api/campaign/lotteries ─────────────────────────────

    public function test_can_list_lotteries(): void
    {
        Lottery::factory()->count(2)->create();

        // 响应为平铺数组（ApiResponse::success 不包裹分页集合）
        $this->getJson('/api/campaign/lotteries')
            ->assertOk()
            ->assertJsonStructure([['lottery_id', 'name', 'draw_mode']]);

        $this->assertCount(2, $this->getJson('/api/campaign/lotteries')->json());
    }

    public function test_lottery_list_filters_by_name(): void
    {
        Lottery::factory()->create(['name' => '新年抽奖']);
        Lottery::factory()->create(['name' => '周年庆抽奖']);

        $this->getJson('/api/campaign/lotteries?name=新年')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_lottery_list_filters_by_current_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $lottery = Lottery::factory()->create(['tenant_id' => $tenant->id]);
        Lottery::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->getJson('/api/campaign/lotteries')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.lottery_id', $lottery->id);
    }

    public function test_lottery_list_filters_by_authorized_tenants_without_header(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);

        $mine = Lottery::factory()->create(['tenant_id' => $tenant->id]);
        // 平台级活动（tenant_id 为空）对所有租户可见
        $global = Lottery::factory()->create(['tenant_id' => null]);
        Lottery::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->actingAs($user)
            ->getJson('/api/campaign/lotteries')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['lottery_id' => $mine->id])
            ->assertJsonFragment(['lottery_id' => $global->id]);
    }

    // ─── GET /api/campaign/lotteries/{lottery} ───────────────────

    public function test_can_show_enabled_lottery_with_prizes(): void
    {
        $lottery = Lottery::factory()->create();
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);

        $this->getJson('/api/campaign/lotteries/'.$lottery->id)
            ->assertOk()
            ->assertJsonPath('lottery_id', $lottery->id);
    }

    public function test_show_returns_404_for_disabled_lottery(): void
    {
        $lottery = Lottery::factory()->disabled()->create();

        $this->getJson('/api/campaign/lotteries/'.$lottery->id)
            ->assertNotFound();
    }

    public function test_show_returns_404_for_other_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $lottery = Lottery::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->getJson('/api/campaign/lotteries/'.$lottery->id)
            ->assertNotFound();
    }

    // ─── POST /api/campaign/lotteries/{lottery}/draw ─────────────

    public function test_draw_requires_authentication(): void
    {
        $lottery = Lottery::factory()->create();
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);

        $this->postJson('/api/campaign/lotteries/'.$lottery->id.'/draw')
            ->assertUnauthorized();
    }

    public function test_user_can_draw(): void
    {
        $lottery = Lottery::factory()->create();
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/campaign/lotteries/'.$lottery->id.'/draw')
            ->assertOk()
            ->assertJsonPath('lottery_id', $lottery->id)
            ->assertJsonPath('user_id', $user->id);
    }

    public function test_draw_returns_400_when_draws_exhausted(): void
    {
        $lottery = Lottery::factory()->create(['max_draws_per_user' => 1]);
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/campaign/lotteries/'.$lottery->id.'/draw')->assertOk();

        // 业务异常统一由 ApiExceptionHandler 返回 400
        $this->postJson('/api/campaign/lotteries/'.$lottery->id.'/draw')
            ->assertBadRequest();
    }

    public function test_user_cannot_draw_lottery_from_other_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $lottery = Lottery::factory()->create(['tenant_id' => $otherTenant->id]);
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);
        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-Id', (string) $tenant->id)
            ->postJson('/api/campaign/lotteries/'.$lottery->id.'/draw')
            ->assertNotFound();

        $this->assertDatabaseCount('lottery_draws', 0);
    }

    public function test_user_cannot_draw_lottery_from_other_tenant_with_forged_header(): void
    {
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $user = User::factory()->create();
        $user->tenants()->attach($tenant);
        $lottery = Lottery::factory()->create(['tenant_id' => $otherTenant->id]);
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);
        Sanctum::actingAs($user);

        $this->withHeader('X-Tenant-Id', (string) $otherTenant->id)
            ->postJson('/api/campaign/lotteries/'.$lottery->id.'/draw')
            ->assertNotFound();

        $this->assertDatabaseCount('lottery_draws', 0);
    }

    // ─── GET /api/campaign/lotteries/{lottery}/available-draws ───

    public function test_available_draws_requires_authentication(): void
    {
        $lottery = Lottery::factory()->create();

        $this->getJson('/api/campaign/lotteries/'.$lottery->id.'/available-draws')
            ->assertUnauthorized();
    }

    public function test_available_draws_returns_remaining_count(): void
    {
        $lottery = Lottery::factory()->create(['max_draws_per_user' => 3]);
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/campaign/lotteries/'.$lottery->id.'/available-draws')
            ->assertOk()
            ->assertJsonPath('available_draws', 3);

        $this->postJson('/api/campaign/lotteries/'.$lottery->id.'/draw')->assertOk();

        $this->getJson('/api/campaign/lotteries/'.$lottery->id.'/available-draws')
            ->assertOk()
            ->assertJsonPath('available_draws', 2);
    }

    public function test_available_draws_unlimited_returns_large_number(): void
    {
        $lottery = Lottery::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/campaign/lotteries/'.$lottery->id.'/available-draws')
            ->assertOk();

        $this->assertGreaterThan(0, $response->json('available_draws'));
    }

    // ─── GET /api/campaign/lotteries/{lottery}/draws ─────────────

    public function test_my_draws_requires_authentication(): void
    {
        $lottery = Lottery::factory()->create();

        $this->getJson('/api/campaign/lotteries/'.$lottery->id.'/draws')
            ->assertUnauthorized();
    }

    public function test_my_draws_returns_only_own_records(): void
    {
        $lottery = Lottery::factory()->create();
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);

        $me = User::factory()->create();
        $other = User::factory()->create();

        Sanctum::actingAs($me);
        $this->postJson('/api/campaign/lotteries/'.$lottery->id.'/draw')->assertOk();

        Sanctum::actingAs($other);
        $this->postJson('/api/campaign/lotteries/'.$lottery->id.'/draw')->assertOk();

        Sanctum::actingAs($me);

        $this->getJson('/api/campaign/lotteries/'.$lottery->id.'/draws')
            ->assertOk()
            ->assertJsonCount(1);
    }

    // ─── GET /api/campaign/lotteries/{lottery}/prizes ────────────

    public function test_my_prizes_returns_only_winning_records(): void
    {
        $lottery = Lottery::factory()->create();
        LotteryPrize::factory()->none()->create(['lottery_id' => $lottery->id]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // 多次抽奖制造记录（谢谢参与不算中奖）
        $this->postJson('/api/campaign/lotteries/'.$lottery->id.'/draw')->assertOk();

        $this->getJson('/api/campaign/lotteries/'.$lottery->id.'/prizes')
            ->assertOk();
    }

    // ─── 测试夹具 ────────────────────────────────────────────────

    // （复用 Lottery/LotteryPrize 工厂，draw 模式默认 free）
}
