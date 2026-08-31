<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\CaptchaCreateRequest;
use Jason\Captcha\Facades\Captcha;
use Tests\TestCase;

class CaptchaApiTest extends TestCase
{
    // ─── GET /api/auth/captcha ────────────────────────────────────

    public function test_can_create_default_captcha(): void
    {
        $response = $this->getJson('/api/auth/captcha');

        $response->assertOk()
            ->assertJsonStructure([
                'type',
                'key',
                'img',
            ])
            ->assertJson([
                'type' => 'default',
            ]);

        $this->assertStringStartsWith('data:image/jpeg;base64,', $response->json('img'));
        $this->assertNotEmpty($response->json('key'));
    }

    public function test_can_create_captcha_of_each_supported_type(): void
    {
        foreach (CaptchaCreateRequest::TYPES as $type) {
            $this->getJson('/api/auth/captcha?type='.$type)
                ->assertOk()
                ->assertJson([
                    'type' => $type,
                ])
                ->assertJsonStructure(['key', 'img']);
        }
    }

    public function test_invalid_type_is_rejected(): void
    {
        $this->getJson('/api/auth/captcha?type=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_generated_captcha_can_be_validated_by_key(): void
    {
        $response = $this->getJson('/api/auth/captcha?type=number');
        $response->assertOk();

        $key = $response->json('key');
        $this->assertNotEmpty($key);

        // 从缓存取出正确答案，用包的 API 校验方法验证一致性
        $stored = cache()->get('captcha_'.md5($key));
        $this->assertNotEmpty($stored);

        $this->assertTrue(Captcha::checkApi($stored, $key, 'number'));
    }
}
