<?php

namespace Tests\Feature\Auth;

use App\Services\Foundation\SmsService;
use Tests\TestCase;

class SmsSendApiTest extends TestCase
{
    public function test_gateway_failure_returns_business_error_instead_of_type_error(): void
    {
        $this->mock(SmsService::class, function ($mock): void {
            $mock->shouldReceive('sendCode')->once()->andReturnFalse();
        });

        $captcha = $this->getJson('/api/auth/captcha')->assertOk();
        $key = $captcha->json('key');
        $code = cache()->get('captcha_'.md5($key));

        $this->postJson('/api/auth/sms', [
            'mobile' => '13800138000',
            'captcha_key' => $key,
            'captcha_code' => $code,
        ])
            ->assertStatus(400)
            ->assertJson([
                'code' => 400,
                'message' => '短信验证码发送失败',
            ]);
    }
}
