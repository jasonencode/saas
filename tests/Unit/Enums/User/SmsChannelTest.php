<?php

namespace Tests\Unit\Enums\User;

use App\Enums\User\SmsChannel;
use PHPUnit\Framework\TestCase;

class SmsChannelTest extends TestCase
{
    public function test_get_label(): void
    {
        $this->assertSame('登录', SmsChannel::Login->getLabel());
        $this->assertSame('注册', SmsChannel::Register->getLabel());
        $this->assertSame('忘记密码', SmsChannel::Forgot->getLabel());
    }

    public function test_get_template(): void
    {
        $this->assertSame('登录验证码', SmsChannel::Login->getTemplate());
        $this->assertSame('注册验证码', SmsChannel::Register->getTemplate());
        $this->assertSame('忘记密码验证码', SmsChannel::Forgot->getTemplate());
    }

    public function test_all_cases_have_labels(): void
    {
        foreach (SmsChannel::cases() as $case) {
            $this->assertNotEmpty($case->getLabel());
        }
    }

    public function test_all_cases_have_templates(): void
    {
        foreach (SmsChannel::cases() as $case) {
            $this->assertNotEmpty($case->getTemplate());
        }
    }
}
