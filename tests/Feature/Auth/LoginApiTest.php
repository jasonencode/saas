<?php

namespace Tests\Feature\Auth;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Jason\Captcha\Facades\Captcha;
use Tests\TestCase;

class LoginApiTest extends TestCase
{
    use RefreshDatabase;

    private function createCaptcha(): array
    {
        $res = Captcha::create('default', true);

        return [
            'captcha_key' => $res['key'],
            'captcha_code' => cache()->get('captcha_'.md5($res['key'])),
        ];
    }

    // ─── POST /api/auth/password ─────────────────────────────────

    public function test_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'username' => 'loginuser',
            'password' => bcrypt('password123'),
        ]);

        $captcha = $this->createCaptcha();

        $response = $this->postJson('/api/auth/password', [
            'username' => 'loginuser',
            'password' => 'password123',
            ...$captcha,
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

        $captcha = $this->createCaptcha();

        $response = $this->postJson('/api/auth/password', [
            'username' => 'loginuser',
            'password' => 'wrongpassword',
            ...$captcha,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_login_fails_with_nonexistent_user(): void
    {
        $captcha = $this->createCaptcha();

        $response = $this->postJson('/api/auth/password', [
            'username' => 'nonexistent',
            'password' => 'password123',
            ...$captcha,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_login_requires_username(): void
    {
        $captcha = $this->createCaptcha();

        $response = $this->postJson('/api/auth/password', [
            'password' => 'password123',
            ...$captcha,
        ]);

        $response->assertStatus(422);
    }

    public function test_login_requires_password(): void
    {
        $captcha = $this->createCaptcha();

        $response = $this->postJson('/api/auth/password', [
            'username' => 'loginuser',
            ...$captcha,
        ]);

        $response->assertStatus(422);
    }

    public function test_login_requires_captcha(): void
    {
        User::factory()->create([
            'username' => 'loginuser',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/password', [
            'username' => 'loginuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['captcha_key', 'captcha_code']);
    }

    public function test_login_fails_with_invalid_captcha(): void
    {
        User::factory()->create([
            'username' => 'loginuser',
            'password' => bcrypt('password123'),
        ]);

        $captcha = $this->createCaptcha();

        $response = $this->postJson('/api/auth/password', [
            'username' => 'loginuser',
            'password' => 'password123',
            'captcha_key' => $captcha['captcha_key'],
            'captcha_code' => '000000',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => '验证码错误',
            ]);
    }

    public function test_login_password_minimum_length(): void
    {
        User::factory()->create([
            'username' => 'loginuser',
            'password' => bcrypt('password123'),
        ]);

        $captcha = $this->createCaptcha();

        $response = $this->postJson('/api/auth/password', [
            'username' => 'loginuser',
            'password' => '12345',
            ...$captcha,
        ]);

        $response->assertStatus(422);
    }

    public function test_login_returns_valid_token(): void
    {
        $user = User::factory()->create([
            'username' => 'tokenuser',
            'password' => bcrypt('password123'),
        ]);

        $captcha = $this->createCaptcha();

        $response = $this->postJson('/api/auth/password', [
            'username' => 'tokenuser',
            'password' => 'password123',
            ...$captcha,
        ]);

        $token = $response->json('token');
        $this->assertNotNull($token);

        // Use the token to access a protected endpoint
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user/profile')
            ->assertOk();
    }
}
