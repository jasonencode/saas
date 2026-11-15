<?php

namespace Tests\Feature\Content;

use App\Models\Content\Comment;
use App\Models\Content\Content;
use App\Models\System\Sensitive;
use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Content $content;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->withHeader('X-Tenant-Id', (string) $this->tenant->id);

        $this->content = Content::factory()->for($this->tenant, 'tenant')->create();
    }

    // ─── GET /api/contents/{content}/comments ───────────────────────

    public function test_can_list_comments_for_content(): void
    {
        $user = User::factory()->create();
        Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'commentable_type' => Content::class,
            'commentable_id' => $this->content->id,
        ]);

        $response = $this->getJson("/api/contents/{$this->content->id}/comments");

        $response->assertOk()
            ->assertJsonStructure([
                'list' => [
                    '*' => [
                        'comment_id',
                        'user' => ['user_id', 'nickname', 'avatar'],
                        'content',
                        'star',
                        'pictures',
                        'created_at',
                    ],
                ],
                'page' => ['current', 'total_page', 'per_page', 'has_more', 'total'],
            ])
            ->assertJsonCount(3, 'list');
    }

    public function test_list_returns_empty_for_content_without_comments(): void
    {
        $response = $this->getJson("/api/contents/{$this->content->id}/comments");

        $response->assertOk()
            ->assertJsonCount(0, 'list');
    }

    public function test_list_returns_404_for_disabled_content(): void
    {
        $disabled = Content::factory()->for($this->tenant, 'tenant')->disabled()->create();

        $response = $this->getJson("/api/contents/{$disabled->id}/comments");

        $response->assertNotFound();
    }

    public function test_list_excludes_disabled_comments(): void
    {
        $user = User::factory()->create();
        Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_type' => Content::class,
            'commentable_id' => $this->content->id,
            'status' => true,
        ]);
        Comment::factory()->disabled()->create([
            'user_id' => $user->id,
            'commentable_type' => Content::class,
            'commentable_id' => $this->content->id,
            'status' => false,
        ]);

        $response = $this->getJson("/api/contents/{$this->content->id}/comments");

        $response->assertOk()
            ->assertJsonCount(1, 'list');
    }

    public function test_list_includes_user_profile_data(): void
    {
        $user = User::factory()->create();
        $user->profile->update(['nickname' => '测试用户']);

        Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_type' => Content::class,
            'commentable_id' => $this->content->id,
        ]);

        $response = $this->getJson("/api/contents/{$this->content->id}/comments");

        $response->assertOk()
            ->assertJsonPath('list.0.user.nickname', '测试用户');
    }

    public function test_list_respects_per_page_parameter(): void
    {
        $user = User::factory()->create();
        Comment::factory()->count(20)->create([
            'user_id' => $user->id,
            'commentable_type' => Content::class,
            'commentable_id' => $this->content->id,
        ]);

        $response = $this->getJson("/api/contents/{$this->content->id}/comments?per_page=5");

        $response->assertOk()
            ->assertJsonCount(5, 'list')
            ->assertJsonPath('page.per_page', 5);
    }

    public function test_list_caps_per_page_at_50(): void
    {
        $user = User::factory()->create();
        Comment::factory()->count(60)->create([
            'user_id' => $user->id,
            'commentable_type' => Content::class,
            'commentable_id' => $this->content->id,
        ]);

        $response = $this->getJson("/api/contents/{$this->content->id}/comments?per_page=100");

        $response->assertOk()
            ->assertJsonCount(50, 'list')
            ->assertJsonPath('page.per_page', 50);
    }

    // ─── POST /api/contents/{content}/comments ──────────────────────

    public function test_can_store_long_comment_within_2000_chars(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/contents/{$this->content->id}/comments", [
                'content' => str_repeat('长', 1000),
            ]);

        $response->assertCreated();
        $this->assertSame(1000, mb_strlen($response->json('content')));
    }

    public function test_can_store_comment_with_auth(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/contents/{$this->content->id}/comments", [
                'content' => '这是一条评论',
                'star' => 5,
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'comment_id',
                'user' => ['user_id', 'nickname', 'avatar'],
                'content',
                'star',
                'pictures',
                'created_at',
            ])
            ->assertJson([
                'content' => '这是一条评论',
                'star' => 5,
            ]);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_type' => Content::class,
            'commentable_id' => $this->content->id,
            'content' => '这是一条评论',
            'star' => 5,
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson("/api/contents/{$this->content->id}/comments", [
            'content' => '未登录评论',
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_requires_content_without_pictures(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/contents/{$this->content->id}/comments", []);

        $response->assertStatus(422);
    }

    public function test_store_accepts_pictures_without_content(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/contents/{$this->content->id}/comments", [
                'pictures' => [rtrim((string) config('app.url'), '/').'/storage/comments/img1.jpg'],
            ]);

        $response->assertCreated();
    }

    public function test_store_rejects_external_pictures(): void
    {
        $user = User::factory()->create();

        // 图片仅允许站内地址，防止外链图片与钓鱼链接
        $response = $this->actingAs($user)
            ->postJson("/api/contents/{$this->content->id}/comments", [
                'content' => '测试评论',
                'pictures' => ['https://example.com/img1.jpg'],
            ]);

        $response->assertBadRequest()
            ->assertJson(['message' => '评论图片仅支持站内地址']);

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_store_rejects_sensitive_content(): void
    {
        $user = User::factory()->create();

        Sensitive::create(['keywords' => '违规词']);

        $response = $this->actingAs($user)
            ->postJson("/api/contents/{$this->content->id}/comments", [
                'content' => '这条包含违规词的评论',
            ]);

        $response->assertBadRequest()
            ->assertJson(['message' => '评论内容包含违规信息']);

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_store_validates_star_range(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/contents/{$this->content->id}/comments", [
                'content' => '测试评论',
                'star' => 6,
            ]);

        $response->assertStatus(422);
    }

    public function test_store_validates_star_minimum(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/contents/{$this->content->id}/comments", [
                'content' => '测试评论',
                'star' => 0,
            ]);

        $response->assertStatus(422);
    }

    public function test_store_validates_pictures_array(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/contents/{$this->content->id}/comments", [
                'pictures' => 'not-an-array',
            ]);

        $response->assertStatus(422);
    }

    public function test_store_validates_content_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/contents/{$this->content->id}/comments", [
                'content' => str_repeat('a', 2001),
            ]);

        $response->assertStatus(422);
    }

    public function test_store_returns_404_for_disabled_content(): void
    {
        $user = User::factory()->create();
        $disabled = Content::factory()->for($this->tenant, 'tenant')->disabled()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/contents/{$disabled->id}/comments", [
                'content' => '测试评论',
            ]);

        $response->assertNotFound();
    }

    public function test_store_returns_404_for_soft_deleted_content(): void
    {
        $user = User::factory()->create();
        $deleted = Content::factory()->for($this->tenant, 'tenant')->create();
        $deleted->delete();

        $response = $this->actingAs($user)
            ->postJson("/api/contents/{$deleted->id}/comments", [
                'content' => '测试评论',
            ]);

        $response->assertNotFound();
    }

    public function test_store_sets_status_to_enabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/contents/{$this->content->id}/comments", [
                'content' => '测试评论',
            ]);

        $this->assertDatabaseHas('comments', [
            'commentable_id' => $this->content->id,
            'status' => true,
        ]);
    }

    public function test_store_leaves_star_null_when_not_provided(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/contents/{$this->content->id}/comments", [
                'content' => '测试评论',
            ]);

        // 未传评分存 null，与 0 分区分
        $this->assertDatabaseHas('comments', [
            'commentable_id' => $this->content->id,
            'star' => null,
        ]);
    }
}
