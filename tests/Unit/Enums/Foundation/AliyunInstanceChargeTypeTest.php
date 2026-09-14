<?php

namespace Tests\Unit\Enums\Foundation;

use App\Enums\Foundation\AliyunInstanceChargeType;
use PHPUnit\Framework\TestCase;

class AliyunInstanceChargeTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('PostPaid', AliyunInstanceChargeType::PostPaid->value);
        $this->assertSame('PrePaid', AliyunInstanceChargeType::PrePaid->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(AliyunInstanceChargeType::PostPaid, AliyunInstanceChargeType::from('PostPaid'));
        $this->assertSame(AliyunInstanceChargeType::PrePaid, AliyunInstanceChargeType::from('PrePaid'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(AliyunInstanceChargeType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, AliyunInstanceChargeType::cases());
    }

    public function test_post_paid_label_and_color(): void
    {
        $this->assertSame('按量付费', AliyunInstanceChargeType::PostPaid->getLabel());
        $this->assertSame('info', AliyunInstanceChargeType::PostPaid->getColor());
    }

    public function test_pre_paid_label_and_color(): void
    {
        $this->assertSame('包年包月', AliyunInstanceChargeType::PrePaid->getLabel());
        $this->assertSame('primary', AliyunInstanceChargeType::PrePaid->getColor());
    }
}
