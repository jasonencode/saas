<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiniProgramLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_login_code(): void
    {
        $this->postJson('/api/auth/mini/phone', [
            'code' => 'phone-code',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('login_code');
    }

    public function test_login_requires_phone_code(): void
    {
        $this->postJson('/api/auth/mini/phone', [
            'login_code' => 'login-code',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_login_fails_when_mini_program_not_configured(): void
    {
        $this->postJson('/api/auth/mini/phone', [
            'code' => 'phone-code',
            'login_code' => 'login-code',
        ])->assertBadRequest()
            ->assertJson(['message' => '租户未配置微信小程序']);
    }
}
