<?php

namespace Tests\Unit\Enums\Foundation;

use App\Enums\Foundation\AliyunDomainStatus;
use PHPUnit\Framework\TestCase;

class AliyunDomainStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame(1, AliyunDomainStatus::NEED_RENEW->value);
        $this->assertSame(2, AliyunDomainStatus::NEED_REDEMPTION->value);
        $this->assertSame(3, AliyunDomainStatus::NORMAL->value);
    }

    public function test_enum_from_int(): void
    {
        $this->assertSame(AliyunDomainStatus::NEED_RENEW, AliyunDomainStatus::from(1));
        $this->assertSame(AliyunDomainStatus::NEED_REDEMPTION, AliyunDomainStatus::from(2));
        $this->assertSame(AliyunDomainStatus::NORMAL, AliyunDomainStatus::from(3));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(AliyunDomainStatus::tryFrom(99));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(3, AliyunDomainStatus::cases());
    }

    public function test_need_renew_label_and_color(): void
    {
        $this->assertSame('急需续费', AliyunDomainStatus::NEED_RENEW->getLabel());
        $this->assertSame('warning', AliyunDomainStatus::NEED_RENEW->getColor());
    }

    public function test_need_redemption_label_and_color(): void
    {
        $this->assertSame('急需赎回', AliyunDomainStatus::NEED_REDEMPTION->getLabel());
        $this->assertSame('danger', AliyunDomainStatus::NEED_REDEMPTION->getColor());
    }

    public function test_normal_label_and_color(): void
    {
        $this->assertSame('正常', AliyunDomainStatus::NORMAL->getLabel());
        $this->assertSame('success', AliyunDomainStatus::NORMAL->getColor());
    }
}
