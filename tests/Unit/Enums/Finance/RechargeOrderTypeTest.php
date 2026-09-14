<?php

namespace Tests\Unit\Enums\Finance;

use App\Enums\Finance\RechargeOrderType;
use PHPUnit\Framework\TestCase;

class RechargeOrderTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('balance', RechargeOrderType::Balance->value);
        $this->assertSame('points', RechargeOrderType::Points->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(RechargeOrderType::Balance, RechargeOrderType::from('balance'));
        $this->assertSame(RechargeOrderType::Points, RechargeOrderType::from('points'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(RechargeOrderType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, RechargeOrderType::cases());
    }

    public function test_balance_label_and_color(): void
    {
        $this->assertSame('余额充值', RechargeOrderType::Balance->getLabel());
        $this->assertSame('success', RechargeOrderType::Balance->getColor());
    }

    public function test_points_label_and_color(): void
    {
        $this->assertSame('积分充值', RechargeOrderType::Points->getLabel());
        $this->assertSame('warning', RechargeOrderType::Points->getColor());
    }
}
