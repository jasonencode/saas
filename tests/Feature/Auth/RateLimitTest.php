<?php

namespace Tests\Feature\Auth;

use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 收窄阈值，让断言不依赖 .env / config 默认值
        config([
            'custom.rate_limits.login' => 2,
            'custom.rate_limits.login_ip' => 3,
            'custom.rate_limits.sms' => 2,
            'custom.rate_limits.sms_ip' => 3,
            'custom.rate_limits.register' => 2,
            'custom.rate_limits.tenant' => 2,
            'custom.rate_limits.upload' => 2,
        ]);
    }

    // ─── 登录限流 ────────────────────────────────────────────────

    public function test_login_is_limited_per_account(): void
    {
        config(['custom.rate_limits.login_ip' => 10]);

        $this->attemptLogin('attacker');
        $this->attemptLogin('attacker');

        // 第 3 次触发账号维度上限（login = 2）
        $this->assertThrottled($this->loginResponse('attacker'));
    }

    public function test_login_quota_is_separated_per_account(): void
    {
        config(['custom.rate_limits.login_ip' => 10]);

        $this->attemptLogin('alice');
        $this->attemptLogin('alice');

        // 同一 IP 但换账号，不应被 alice 的配额牵连
        $this->attemptLogin('bob');
    }

    public function test_login_ip_ceiling_caps_distinct_accounts(): void
    {
        config([
            'custom.rate_limits.login' => 5,
            'custom.rate_limits.login_ip' => 3,
        ]);

        $this->attemptLogin('u1');
        $this->attemptLogin('u2');
        $this->attemptLogin('u3');

        // 账号各不相同，第 4 次命中 IP 总上限
        $this->assertThrottled($this->loginResponse('u4'));
    }

    public function test_login_quota_is_separated_per_ip(): void
    {
        config([
            'custom.rate_limits.login' => 10,
            'custom.rate_limits.login_ip' => 2,
        ]);

        $this->withHeader('X-Forwarded-For', '203.0.113.10');
        $this->attemptLogin('shared');
        $this->attemptLogin('shared');
        $this->assertThrottled($this->loginResponse('shared'));

        // 换一个出口 IP 后配额独立，共享 NAT 出口不会互相误伤
        $this->withHeader('X-Forwarded-For', '198.51.100.7');
        $this->attemptLogin('shared');
    }

    // ─── 短信限流 ────────────────────────────────────────────────

    public function test_sms_is_limited_per_mobile(): void
    {
        config(['custom.rate_limits.sms_ip' => 10]);

        $this->attemptSms('13800000000');
        $this->attemptSms('13800000000');

        // 第 3 次触发手机号维度上限（sms = 2）
        $this->assertThrottled($this->smsResponse('13800000000'));

        // 换手机号不受影响
        $this->attemptSms('13900000000');
    }

    // ─── 注册限流 ────────────────────────────────────────────────

    public function test_register_is_limited_per_ip(): void
    {
        $this->attemptRegister();
        $this->attemptRegister();

        $this->assertThrottled($this->postJson('/api/auth/register', [
            'username' => 'tester',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]));
    }

    // ─── 租户令牌限流 ────────────────────────────────────────────

    public function test_tenant_is_limited_per_app_key(): void
    {
        $payload = ['app_key' => 'app-key-a', 'timestamp' => time(), 'nonce' => 'n', 'signature' => 's'];

        $this->assertNotSame(429, $this->postJson('/api/auth/tenant', $payload)->getStatusCode());
        $this->assertNotSame(429, $this->postJson('/api/auth/tenant', $payload)->getStatusCode());

        $this->assertThrottled($this->postJson('/api/auth/tenant', $payload));

        // 换 app_key 独立计数
        $this->assertNotSame(429, $this->postJson('/api/auth/tenant', [
            'app_key' => 'app-key-b', 'timestamp' => time(), 'nonce' => 'n', 'signature' => 's',
        ])->getStatusCode());
    }

    // ─── 上传限流 ────────────────────────────────────────────────

    public function test_upload_is_limited_per_user(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->assertNotSame(429, $this->postJson('/api/system/upload/image')->getStatusCode());
        $this->assertNotSame(429, $this->postJson('/api/system/upload/image')->getStatusCode());

        $this->assertThrottled($this->postJson('/api/system/upload/image'));
    }

    // ─── 非字符串入参不得让限流器报错 ───────────────────────────

    public function test_array_payload_does_not_break_the_limiters(): void
    {
        // 中间件先于表单验证执行，此处入参还未被校验
        $cases = [
            '/api/auth/password' => ['username' => ['a', 'b'], 'password' => 'x'],
            '/api/auth/sms' => ['mobile' => ['a', 'b']],
            '/api/auth/tenant' => ['app_key' => ['a', 'b']],
        ];

        foreach ($cases as $uri => $payload) {
            $response = $this->postJson($uri, $payload);

            $this->assertLessThan(500, $response->getStatusCode(), "{$uri} 返回了 ".$response->getStatusCode().': '.$response->getContent());
        }
    }

    // ─── 辅助方法 ────────────────────────────────────────────────

    /**
     * 发一次登录请求（不含验证码，注定校验失败，但会占用限流配额）
     */
    private function loginResponse(string $username): TestResponse
    {
        return $this->postJson('/api/auth/password', [
            'username' => $username,
            'password' => 'wrongpassword',
        ]);
    }

    private function attemptLogin(string $username): void
    {
        $this->assertNotSame(429, $this->loginResponse($username)->getStatusCode(), "账号 {$username} 被意外限流");
    }

    private function smsResponse(string $mobile): TestResponse
    {
        return $this->postJson('/api/auth/sms', ['mobile' => $mobile]);
    }

    private function attemptSms(string $mobile): void
    {
        $this->assertNotSame(429, $this->smsResponse($mobile)->getStatusCode(), "手机号 {$mobile} 被意外限流");
    }

    private function attemptRegister(): void
    {
        $response = $this->postJson('/api/auth/register', ['username' => 'tester']);

        $this->assertNotSame(429, $response->getStatusCode(), '注册请求被意外限流');
    }

    /**
     * 断言响应为限流响应
     */
    private function assertThrottled(TestResponse $response): void
    {
        $response->assertStatus(429)
            ->assertJsonPath('message', '请求过于频繁，请稍后再试');
    }
}
