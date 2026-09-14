<?php

namespace Tests\Feature\User;

use App\Models\User\User;
use App\Models\User\UserRelation;
use App\Services\User\UserRelationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserRelationApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── GET /api/user/relations ─────────────────────────────────

    public function test_index_returns_empty_structure_when_user_has_no_relation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/user/relations')
            ->assertOk()
            ->assertJsonPath('parent', null)
            ->assertJsonPath('list', []);
    }

    public function test_index_returns_parent_and_descendants(): void
    {
        [$root, $parent, $child] = $this->makeChain();
        Sanctum::actingAs($parent);

        $this->getJson('/api/user/relations')
            ->assertOk()
            ->assertJsonPath('parent.user_id', $root->id)
            ->assertJsonPath('parent.username', $root->username);

        $list = $this->getJson('/api/user/relations')->json('list');
        $this->assertCount(1, $list);
        $this->assertSame($child->id, $list[0]['user_id']);
    }

    public function test_index_for_leaf_user_returns_parent_without_descendants(): void
    {
        [, , $child] = $this->makeChain();
        Sanctum::actingAs($child);

        $response = $this->getJson('/api/user/relations')
            ->assertOk();

        $this->assertNotNull($response->json('parent'));
        $this->assertCount(0, $response->json('list'));
    }

    // ─── POST /api/user/relations/bind/{parentId} ────────────────

    public function test_bind_creates_relation(): void
    {
        $parent = User::factory()->create();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/relations/bind/'.$parent->id)
            ->assertOk()
            ->assertJsonPath('code', 0);

        $this->assertDatabaseHas('user_relations', [
            'user_id' => $user->id,
            'parent_id' => $parent->id,
        ]);
    }

    public function test_bind_rejects_nonexistent_parent(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/relations/bind/99999')
            ->assertStatus(400);

        $this->assertDatabaseMissing('user_relations', [
            'user_id' => $user->id,
        ]);
    }

    public function test_bind_rejects_duplicate_binding(): void
    {
        [, $parent, $child] = $this->makeChain();
        Sanctum::actingAs($child);

        $this->postJson('/api/user/relations/bind/'.$parent->id)
            ->assertStatus(400);
    }

    // ─── GET /api/user/relations/overview ────────────────────────

    public function test_overview_returns_team_stats(): void
    {
        [$root, $parent, $child] = $this->makeChain();
        Sanctum::actingAs($parent);

        $this->getJson('/api/user/relations/overview')
            ->assertOk()
            ->assertJsonPath('team_count', 1)
            ->assertJsonPath('total_commission', 0);
    }

    public function test_overview_without_relation_returns_zero_team(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/user/relations/overview')
            ->assertOk()
            ->assertJsonPath('team_count', 0);
    }

    // ─── GET /api/user/{user} (公开接口) ─────────────────────────

    public function test_public_user_endpoint_returns_profile_summary(): void
    {
        $user = User::factory()->create();
        $user->profile()->update(['nickname' => '公开昵称']);

        $this->getJson('/api/user/'.$user->id)
            ->assertOk()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('nickname', '公开昵称');
    }

    public function test_public_user_endpoint_returns_404_for_missing_user(): void
    {
        $this->getJson('/api/user/99999')->assertNotFound();
    }

    // ─── 测试夹具 ────────────────────────────────────────────────

    /**
     * 创建 root -> parent -> child 三级隶属链（走 service，计数字段同步更新）
     *
     * @return array{0: User, 1: User, 2: User}
     */
    private function makeChain(): array
    {
        $root = User::factory()->create();
        $parent = User::factory()->create();
        $child = User::factory()->create();

        UserRelation::create([
            'user_id' => $root->id,
            'parent_id' => null,
            'layer' => 0,
            'path' => "/{$root->id}/",
        ]);

        $service = app(UserRelationService::class);
        $service->createRelation($parent, $root->id);
        $service->createRelation($child, $parent->id);

        return [$root, $parent, $child];
    }
}
