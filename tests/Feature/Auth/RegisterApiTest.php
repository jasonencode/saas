<?php

namespace Tests\Feature\Auth;

use App\Models\System\Tenant;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterApiTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
    }

    // ─── POST /api/auth/register ─────────────────────────────────

    public function test_can_register_with_valid_data(): void
    {
        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/auth/register', [
                'username' => 'testuser',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'user_id',
                'username',
                'profile' => ['nickname', 'avatar', 'gender', 'birthday'],
            ])
            ->assertJson([
                'username' => 'testuser',
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
        ]);
    }

    public function test_register_creates_user_profile(): void
    {
        $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/auth/register', [
                'username' => 'profileuser',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $this->assertDatabaseHas('users', ['username' => 'profileuser']);

        $user = User::where('username', 'profileuser')->first();
        $this->assertNotNull($user->profile);
    }

    public function test_register_requires_username(): void
    {
        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/auth/register', [
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        // 参数校验失败是 422（见 docs/guide/api.md 状态码约定）
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_register_requires_password(): void
    {
        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/auth/register', [
                'username' => 'nopassworduser',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', ['username' => 'nopassworduser']);
    }

    public function test_register_rejects_mismatched_confirmation(): void
    {
        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/auth/register', [
                'username' => 'mismatchuser',
                'password' => 'password123',
                'password_confirmation' => 'password456',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', ['username' => 'mismatchuser']);
    }

    /**
     * 注册接口是租户无关的：只写全局 users 记录，不消费 X-Tenant-Id。
     *
     * 原断言是「缺少租户头时返回 400」(test_register_fails_without_tenant)，
     * 但该逻辑在代码里并不存在：users 表没有 tenant_id，RegisterController 不读租户，
     * UserCreatedEvent 也没有任何监听器去绑定租户。
     *
     * 若产品上要求「注册必须绑定租户」，这里应该改为断言 400/403，并在控制器里补校验。
     */
    public function test_register_is_tenant_agnostic(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'username' => 'tenantlessuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', ['username' => 'tenantlessuser']);
    }
}

    public function test_register_fails_with_duplicate_username(): void
    {
        $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/auth/register', [
                'username' => 'duplicate_user',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/auth/register', [
                'username' => 'duplicate_user',
                'password' => 'password456',
                'password_confirmation' => 'password456',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }
}
