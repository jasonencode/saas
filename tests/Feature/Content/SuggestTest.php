<?php

namespace Tests\Feature\Content;

use App\Enums\Content\SuggestStatus;
use App\Models\Content\Suggest;
use App\Models\Content\SuggestMessage;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuggestTest extends TestCase
{
    use RefreshDatabase;

    // ─── POST /api/contents/suggests ───────────────────────

    public function test_can_store_suggest_with_full_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/contents/suggests', [
                'type' => 'bug',
                'content' => '商品详情页图片加载不出来',
                'contact' => '13800138000',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'suggest_id',
                'type',
                'contact',
                'status',
                'created_at',
            ])
            ->assertJson([
                'type' => ['value' => 'bug'],
                'contact' => '13800138000',
                'status' => ['value' => 'pending'],
            ]);

        $this->assertDatabaseHas('suggests', [
            'user_id' => $user->id,
            'type' => 'bug',
            'contact' => '13800138000',
            'status' => 'pending',
        ]);
    }

    public function test_can_store_suggest_without_contact(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/contents/suggests', [
                'type' => 'feature',
                'content' => '希望增加夜间模式',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('suggests', [
            'user_id' => $user->id,
            'contact' => null,
        ]);
    }

    public function test_store_creates_first_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/contents/suggests', [
                'type' => 'bug',
                'content' => '测试反馈',
            ]);

        $suggest = Suggest::first();

        $this->assertDatabaseHas('suggest_messages', [
            'suggest_id' => $suggest->id,
            'sender_type' => User::class,
            'sender_id' => $user->id,
            'content' => '测试反馈',
        ]);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/contents/suggests', [
            'type' => 'bug',
            'content' => '测试反馈',
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_requires_content(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/contents/suggests', [
                'type' => 'bug',
            ]);

        $response->assertStatus(422);
    }

    public function test_store_validates_content_min_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/contents/suggests', [
                'type' => 'bug',
                'content' => '测试',
            ]);

        $response->assertStatus(422);
    }

    public function test_store_validates_content_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/contents/suggests', [
                'type' => 'bug',
                'content' => str_repeat('a', 501),
            ]);

        $response->assertStatus(422);
    }

    public function test_store_validates_type(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/contents/suggests', [
                'type' => 'invalid',
                'content' => '测试反馈',
            ]);

        $response->assertStatus(422);
    }

    // ─── GET /api/contents/suggests ───────────────────────

    public function test_can_list_suggests(): void
    {
        $user = User::factory()->create();
        Suggest::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/contents/suggests');

        $response->assertOk()
            ->assertJsonStructure([
                'list' => [
                    '*' => [
                        'suggest_id',
                        'type',
                        'contact',
                        'status',
                        'last_message_at',
                        'created_at',
                    ],
                ],
                'page' => ['current_page', 'total_page', 'per_page', 'has_more', 'total'],
            ])
            ->assertJsonCount(3, 'list');
    }

    public function test_list_filters_by_status(): void
    {
        $user = User::factory()->create();
        Suggest::factory()->create(['user_id' => $user->id, 'status' => SuggestStatus::Pending]);
        Suggest::factory()->create(['user_id' => $user->id, 'status' => SuggestStatus::Resolved]);

        $response = $this->actingAs($user)
            ->getJson('/api/contents/suggests?status=pending');

        $response->assertOk()
            ->assertJsonCount(1, 'list')
            ->assertJsonPath('list.0.status.value', 'pending');
    }

    public function test_list_only_shows_own_suggests(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Suggest::factory()->create(['user_id' => $user1->id]);
        Suggest::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)
            ->getJson('/api/contents/suggests');

        $response->assertOk()
            ->assertJsonCount(1, 'list');
    }

    public function test_list_requires_authentication(): void
    {
        $response = $this->getJson('/api/contents/suggests');

        $response->assertUnauthorized();
    }

    public function test_list_validates_status(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/contents/suggests?status=invalid');

        $response->assertStatus(422);
    }

    // ─── GET /api/contents/suggests/{suggest} ───────────────────────

    public function test_can_show_suggest_messages(): void
    {
        $user = User::factory()->create();
        $suggest = Suggest::factory()->create(['user_id' => $user->id]);
        SuggestMessage::factory()->count(3)->create(['suggest_id' => $suggest->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/contents/suggests/{$suggest->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'suggest' => [
                    'suggest_id',
                    'type',
                    'status',
                ],
                'messages' => [
                    'list' => [
                        '*' => [
                            'message_id',
                            'content',
                            'is_from_user',
                            'sender',
                            'created_at',
                        ],
                    ],
                    'page',
                ],
            ])
            ->assertJsonCount(3, 'messages.list');
    }

    public function test_show_requires_authentication(): void
    {
        $suggest = Suggest::factory()->create();

        $response = $this->getJson("/api/contents/suggests/{$suggest->id}");

        $response->assertUnauthorized();
    }

    public function test_show_returns_404_for_non_existent_suggest(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/contents/suggests/999');

        $response->assertNotFound();
    }

    public function test_show_forbids_access_to_others_suggest(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $suggest = Suggest::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)
            ->getJson("/api/contents/suggests/{$suggest->id}");

        $response->assertForbidden();
    }

    // ─── POST /api/contents/suggests/{suggest}/messages ───────────────────────

    public function test_can_append_message(): void
    {
        $user = User::factory()->create();
        $suggest = Suggest::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/contents/suggests/{$suggest->id}/messages", [
                'content' => '追加消息',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'message_id',
                'content',
                'is_from_user',
                'sender',
                'created_at',
            ])
            ->assertJson([
                'content' => '追加消息',
                'is_from_user' => true,
            ]);

        $this->assertDatabaseHas('suggest_messages', [
            'suggest_id' => $suggest->id,
            'sender_type' => User::class,
            'sender_id' => $user->id,
            'content' => '追加消息',
        ]);
    }

    public function test_append_message_requires_authentication(): void
    {
        $suggest = Suggest::factory()->create();

        $response = $this->postJson("/api/contents/suggests/{$suggest->id}/messages", [
            'content' => '测试消息',
        ]);

        $response->assertUnauthorized();
    }

    public function test_append_message_returns_404_for_non_existent_suggest(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/contents/suggests/999/messages', [
                'content' => '测试消息',
            ]);

        $response->assertNotFound();
    }

    public function test_append_message_forbids_access_to_others_suggest(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $suggest = Suggest::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)
            ->postJson("/api/contents/suggests/{$suggest->id}/messages", [
                'content' => '测试消息',
            ]);

        $response->assertForbidden();
    }

    public function test_append_message_forbids_closed_suggest(): void
    {
        $user = User::factory()->create();
        $suggest = Suggest::factory()->create([
            'user_id' => $user->id,
            'status' => SuggestStatus::Closed,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/contents/suggests/{$suggest->id}/messages", [
                'content' => '测试消息',
            ]);

        $response->assertStatus(400);
    }

    public function test_append_message_requires_content(): void
    {
        $user = User::factory()->create();
        $suggest = Suggest::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/contents/suggests/{$suggest->id}/messages", []);

        $response->assertStatus(422);
    }

    public function test_append_message_validates_content_max_length(): void
    {
        $user = User::factory()->create();
        $suggest = Suggest::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson("/api/contents/suggests/{$suggest->id}/messages", [
                'content' => str_repeat('a', 501),
            ]);

        $response->assertStatus(422);
    }
}
