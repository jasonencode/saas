<?php

namespace Tests\Unit\Enums\Campaign;

use App\Enums\Campaign\RedpackCodeStatus;
use PHPUnit\Framework\TestCase;

class RedpackCodeStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('active', RedpackCodeStatus::Active->value);
        $this->assertSame('claimed', RedpackCodeStatus::Claimed->value);
        $this->assertSame('disabled', RedpackCodeStatus::Disabled->value);
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(3, RedpackCodeStatus::cases());
    }

    public function test_active_label_and_color(): void
    {
        $this->assertSame('待领取', RedpackCodeStatus::Active->getLabel());
        $this->assertSame('primary', RedpackCodeStatus::Active->getColor());
    }

    public function test_claimed_label_and_color(): void
    {
        $this->assertSame('已领取', RedpackCodeStatus::Claimed->getLabel());
        $this->assertSame('success', RedpackCodeStatus::Claimed->getColor());
    }

    public function test_disabled_label_and_color(): void
    {
        $this->assertSame('禁用', RedpackCodeStatus::Disabled->getLabel());
        $this->assertSame('warning', RedpackCodeStatus::Disabled->getColor());
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(RedpackCodeStatus::Active, RedpackCodeStatus::from('active'));
        $this->assertSame(RedpackCodeStatus::Claimed, RedpackCodeStatus::from('claimed'));
        $this->assertSame(RedpackCodeStatus::Disabled, RedpackCodeStatus::from('disabled'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(RedpackCodeStatus::tryFrom('invalid'));
    }
}
