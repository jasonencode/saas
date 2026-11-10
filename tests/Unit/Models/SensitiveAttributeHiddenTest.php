<?php

namespace Tests\Unit\Models;

use App\Models\Finance\UserAccount;
use App\Models\Foundation\Alipay;
use App\Models\Foundation\Aliyun;
use App\Models\Foundation\Socialite;
use App\Models\Foundation\SocialiteAccount;
use App\Models\Foundation\Wechat;
use App\Models\Foundation\WechatMini;
use App\Models\Foundation\WechatPayment;
use App\Models\System\Tenant;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SensitiveAttributeHiddenTest extends TestCase
{
    #[DataProvider('sensitiveAttributes')]
    public function test_sensitive_attribute_is_hidden_from_serialization(string $model, string $attribute): void
    {
        $instance = new $model([$attribute => 'secret-value']);

        $this->assertNotNull($instance->getAttribute($attribute));
        $this->assertArrayNotHasKey($attribute, $instance->toArray());
        $this->assertStringNotContainsString('secret-value', json_encode($instance->toArray(), JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, array{0: class-string, 1: string}> 模型与需要隐藏的字段
     */
    public static function sensitiveAttributes(): array
    {
        return [
            'tenant.app_secret' => [Tenant::class, 'app_secret'],
            'user_account.payment_password' => [UserAccount::class, 'payment_password'],
            'aliyun.app_secret' => [Aliyun::class, 'app_secret'],
            'wechat.app_secret' => [Wechat::class, 'app_secret'],
            'wechat_mini.app_secret' => [WechatMini::class, 'app_secret'],
            'wechat_payment.secret' => [WechatPayment::class, 'secret'],
            'wechat_payment.private_key' => [WechatPayment::class, 'private_key'],
            'alipay.private_key' => [Alipay::class, 'private_key'],
            'socialite_account.app_secret' => [SocialiteAccount::class, 'app_secret'],
            'socialite.access_token' => [Socialite::class, 'access_token'],
            'socialite.refresh_token' => [Socialite::class, 'refresh_token'],
        ];
    }
}
