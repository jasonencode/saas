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
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_register_creates_user_profile(): void
    {
        $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/auth/register', [
                'username' => 'profileuser',
                'password' => 'password123',
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
            ]);

        $response->assertStatus(400);
    }

    public function test_register_can_omit_password(): void
    {
        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/auth/register', [
                'username' => 'nopassworduser',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users', ['username' => 'nopassworduser']);
    }

    public function test_register_fails_without_tenant(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(400);
    }

    public function test_register_fails_with_duplicate_username(): void
    {
        $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/auth/register', [
                'username' => 'duplicate_user',
                'password' => 'password123',
            ]);

        $response = $this->withHeader('X-Tenant-Id', $this->tenant->id)
            ->postJson('/api/auth/register', [
                'username' => 'duplicate_user',
                'password' => 'password456',
            ]);

        $response->assertStatus(400);
    }
}
