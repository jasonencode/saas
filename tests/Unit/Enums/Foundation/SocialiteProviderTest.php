<?php

namespace Tests\Unit\Enums\Foundation;

use App\Enums\Foundation\SocialiteProvider;
use PHPUnit\Framework\TestCase;

class SocialiteProviderTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('Alipay', SocialiteProvider::Alipay->value);
        $this->assertSame('Douyin', SocialiteProvider::Douyin->value);
        $this->assertSame('QQ', SocialiteProvider::QQ->value);
        $this->assertSame('Taobao', SocialiteProvider::Taobao->value);
        $this->assertSame('WeChat', SocialiteProvider::WeChat->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(SocialiteProvider::Alipay, SocialiteProvider::from('Alipay'));
        $this->assertSame(SocialiteProvider::Douyin, SocialiteProvider::from('Douyin'));
        $this->assertSame(SocialiteProvider::QQ, SocialiteProvider::from('QQ'));
        $this->assertSame(SocialiteProvider::Taobao, SocialiteProvider::from('Taobao'));
        $this->assertSame(SocialiteProvider::WeChat, SocialiteProvider::from('WeChat'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(SocialiteProvider::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(5, SocialiteProvider::cases());
    }

    public function test_alipay_label(): void
    {
        $this->assertSame('支付宝', SocialiteProvider::Alipay->getLabel());
    }

    public function test_douyin_label(): void
    {
        $this->assertSame('抖音', SocialiteProvider::Douyin->getLabel());
    }

    public function test_qq_label(): void
    {
        $this->assertSame('QQ', SocialiteProvider::QQ->getLabel());
    }

    public function test_taobao_label(): void
    {
        $this->assertSame('淘宝', SocialiteProvider::Taobao->getLabel());
    }

    public function test_wechat_label(): void
    {
        $this->assertSame('微信', SocialiteProvider::WeChat->getLabel());
    }
}
