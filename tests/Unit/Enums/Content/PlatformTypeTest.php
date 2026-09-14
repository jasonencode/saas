<?php

namespace Tests\Unit\Enums\Content;

use App\Enums\Content\PlatformType;
use PHPUnit\Framework\TestCase;

class PlatformTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('android', PlatformType::Android->value);
        $this->assertSame('ios', PlatformType::Ios->value);
        $this->assertSame('apad', PlatformType::AndroidPad->value);
        $this->assertSame('ipad', PlatformType::Ipad->value);
        $this->assertSame('wmp', PlatformType::Wmp->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(PlatformType::Android, PlatformType::from('android'));
        $this->assertSame(PlatformType::Ios, PlatformType::from('ios'));
        $this->assertSame(PlatformType::AndroidPad, PlatformType::from('apad'));
        $this->assertSame(PlatformType::Ipad, PlatformType::from('ipad'));
        $this->assertSame(PlatformType::Wmp, PlatformType::from('wmp'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(PlatformType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(5, PlatformType::cases());
    }

    public function test_android_label(): void
    {
        $this->assertSame('安卓', PlatformType::Android->getLabel());
    }

    public function test_ios_label(): void
    {
        $this->assertSame('IOS', PlatformType::Ios->getLabel());
    }

    public function test_android_pad_label(): void
    {
        $this->assertSame('安卓平板', PlatformType::AndroidPad->getLabel());
    }

    public function test_ipad_label(): void
    {
        $this->assertSame('IPAD平板', PlatformType::Ipad->getLabel());
    }

    public function test_wmp_label(): void
    {
        $this->assertSame('微信小程序', PlatformType::Wmp->getLabel());
    }
}
