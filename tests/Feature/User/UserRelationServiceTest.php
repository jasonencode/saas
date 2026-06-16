<?php

namespace Tests\Feature\User;

use App\Models\User\User;
use App\Models\User\UserRelation;
use App\Services\User\UserRelationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class UserRelationServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserRelationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UserRelationService::class);
    }

    public function test_create_relation_creates_child_relation_and_updates_ancestor_counts(): void
    {
        $root = User::factory()->create();
        $parent = User::factory()->create();
        $child = User::factory()->create();

        UserRelation::create([
            'user_id' => $root->id,
            'parent_id' => $root->id,
            'layer' => 0,
            'path' => "/{$root->id}/",
        ]);

        UserRelation::create([
            'user_id' => $parent->id,
            'parent_id' => $root->id,
            'layer' => 1,
            'path' => "/{$root->id}/{$parent->id}/",
        ]);

        $result = $this->service->createRelation($child, $parent->id);

        $this->assertTrue($result);
        $this->assertDatabaseHas('user_relations', [
            'user_id' => $child->id,
            'parent_id' => $parent->id,
            'layer' => 2,
            'path' => "/{$root->id}/{$parent->id}/{$child->id}/",
        ]);

        $parentRelation = UserRelation::findOrFail($parent->id);
        $this->assertSame(1, $parentRelation->direct_count);
        $this->assertSame(1, $parentRelation->team_count);
    }

    public function test_create_relation_throws_when_relation_already_exists(): void
    {
        $user = User::factory()->create();

        UserRelation::create([
            'user_id' => $user->id,
            'parent_id' => $user->id,
            'layer' => 0,
            'path' => "/{$user->id}/",
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('用户关系已存在');

        $this->service->createRelation($user, $user->id);
    }

    public function test_create_relation_throws_when_parent_is_self(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不能将自己设为推荐人');

        $this->service->createRelation($user, $user->id);
    }

    public function test_create_relation_throws_when_parent_does_not_exist(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('推荐人不存在');

        $this->service->createRelation($user, 999999);
    }

    public function test_update_parent_returns_true_when_parent_is_unchanged(): void
    {
        $parent = User::factory()->create();
        $child = User::factory()->create();

        UserRelation::create([
            'user_id' => $parent->id,
            'parent_id' => $parent->id,
            'layer' => 0,
            'path' => "/{$parent->id}/",
        ]);

        UserRelation::create([
            'user_id' => $child->id,
            'parent_id' => $parent->id,
            'layer' => 1,
            'path' => "/{$parent->id}/{$child->id}/",
        ]);

        $this->assertTrue($this->service->updateParent($child, $parent->id));
    }

    public function test_update_parent_throws_when_relation_does_not_exist(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('用户关系不存在');

        $this->service->updateParent($user, 1);
    }

    public function test_update_parent_throws_when_new_parent_is_descendant(): void
    {
        $grandParent = User::factory()->create();
        $parent = User::factory()->create();
        $child = User::factory()->create();

        UserRelation::create([
            'user_id' => $grandParent->id,
            'parent_id' => $grandParent->id,
            'layer' => 0,
            'path' => "/{$grandParent->id}/",
        ]);

        UserRelation::create([
            'user_id' => $parent->id,
            'parent_id' => $grandParent->id,
            'layer' => 1,
            'path' => "/{$grandParent->id}/{$parent->id}/",
        ]);

        UserRelation::create([
            'user_id' => $child->id,
            'parent_id' => $parent->id,
            'layer' => 2,
            'path' => "/{$grandParent->id}/{$parent->id}/{$child->id}/",
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不能将自己或下级设为推荐人');

        $this->service->updateParent($parent, $child->id);
    }
}
