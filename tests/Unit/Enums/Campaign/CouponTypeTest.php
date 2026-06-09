<?php

namespace Tests\Unit\Enums\Campaign;

use App\Enums\Campaign\CouponType;
use PHPUnit\Framework\TestCase;

class CouponTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('fixed', CouponType::Fixed->value);
        $this->assertSame('percent', CouponType::Percent->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(CouponType::Fixed, CouponType::from('fixed'));
        $this->assertSame(CouponType::Percent, CouponType::from('percent'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(CouponType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, CouponType::cases());
    }

    public function test_fixed_label_and_color(): void
    {
        $this->assertSame('固定金额', CouponType::Fixed->getLabel());
        $this->assertSame('primary', CouponType::Fixed->getColor());
    }

    public function test_percent_label_and_color(): void
    {
        $this->assertSame('百分比', CouponType::Percent->getLabel());
        $this->assertSame('success', CouponType::Percent->getColor());
    }
}
