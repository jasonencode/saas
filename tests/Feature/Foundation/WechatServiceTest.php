<?php

namespace Tests\Feature\Foundation;

use App\Models\Foundation\Wechat;
use App\Models\Foundation\WechatMini;
use App\Services\Foundation\WechatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;
use Throwable;

/**
 * 微信配置读取与连接测试的错误信息
 */
class WechatServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_account_config_is_built_from_stored_credentials(): void
    {
        $wechat = Wechat::create([
            'name' => '测试公众号',
            'app_id' => 'wx_official',
            'app_secret' => 'official_secret',
        ]);

        $this->assertSame([
            'app_id' => 'wx_official',
            'secret' => 'official_secret',
        ], $wechat->getConfig());
    }

    public function test_mini_program_config_is_built_from_stored_credentials(): void
    {
        $wechatMini = WechatMini::create([
            'name' => '测试小程序',
            'app_id' => 'wx_mini',
            'app_secret' => 'mini_secret',
        ]);

        $this->assertSame([
            'app_id' => 'wx_mini',
            'secret' => 'mini_secret',
        ], $wechatMini->getConfig());
    }

    public function test_connection_failure_without_json_uses_a_generic_message(): void
    {
        $message = $this->failureMessage(new RuntimeException('connection timed out'));

        $this->assertSame('微信配置测试失败，请检查 AppID 与 AppSecret', $message);
    }

    public function test_connection_failure_extracts_the_wechat_error_message(): void
    {
        $message = $this->failureMessage(new RuntimeException(
            'cURL error: {"errcode":40013,"errmsg":"invalid appid"}'
        ));

        $this->assertSame('invalid appid', $message);
    }

    private function failureMessage(Throwable $exception): string
    {
        $service = new class extends WechatService {
            public function exposeFailureMessage(Throwable $exception): string
            {
                return $this->failureMessage($exception);
            }
        };

        return $service->exposeFailureMessage($exception);
    }
}
