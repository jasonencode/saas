<?php

namespace Tests\Feature\Auth;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginApiTest extends TestCase
{
    use RefreshDatabase;

    // ─── POST /api/auth/password ─────────────────────────────────

    public function test_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'username' => 'loginuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/password', [
            'username' => 'loginuser',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'type',
                'key',
            ])
            ->assertJson([
                'type' => 'Bearer',
                'key' => 'Authorization',
            ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'username' => 'loginuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/password', [
            'username' => 'loginuser',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_login_fails_with_nonexistent_user(): void
    {
        $response = $this->postJson('/api/auth/password', [
            'username' => 'nonexistent',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_login_requires_username(): void
    {
        $response = $this->postJson('/api/auth/password', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_requires_password(): void
    {
        $response = $this->postJson('/api/auth/password', [
            'username' => 'loginuser',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_password_minimum_length(): void
    {
        User::factory()->create([
            'username' => 'loginuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/password', [
            'username' => 'loginuser',
            'password' => '12345',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_returns_valid_token(): void
    {
        $user = User::factory()->create([
            'username' => 'tokenuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/password', [
            'username' => 'tokenuser',
            'password' => 'password123',
        ]);

        $token = $response->json('token');
        $this->assertNotNull($token);

        // Use the token to access a protected endpoint
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user/profile')
            ->assertOk();
    }
}
