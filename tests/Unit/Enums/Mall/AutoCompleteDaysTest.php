<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\AutoCompleteDays;
use PHPUnit\Framework\TestCase;

class AutoCompleteDaysTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame(3, AutoCompleteDays::Days3->value);
        $this->assertSame(7, AutoCompleteDays::Days7->value);
        $this->assertSame(14, AutoCompleteDays::Days14->value);
        $this->assertSame(30, AutoCompleteDays::Days30->value);
    }

    public function test_enum_from_int(): void
    {
        $this->assertSame(AutoCompleteDays::Days3, AutoCompleteDays::from(3));
        $this->assertSame(AutoCompleteDays::Days7, AutoCompleteDays::from(7));
        $this->assertSame(AutoCompleteDays::Days14, AutoCompleteDays::from(14));
        $this->assertSame(AutoCompleteDays::Days30, AutoCompleteDays::from(30));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(AutoCompleteDays::tryFrom(99));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(4, AutoCompleteDays::cases());
    }

    public function test_days3_label(): void
    {
        $this->assertSame('3天自动完成', AutoCompleteDays::Days3->getLabel());
    }

    public function test_days7_label(): void
    {
        $this->assertSame('7天自动完成', AutoCompleteDays::Days7->getLabel());
    }

    public function test_days14_label(): void
    {
        $this->assertSame('14天自动完成', AutoCompleteDays::Days14->getLabel());
    }

    public function test_days30_label(): void
    {
        $this->assertSame('30天自动完成', AutoCompleteDays::Days30->getLabel());
    }
}
