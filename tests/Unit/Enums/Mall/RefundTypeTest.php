<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\RefundType;
use PHPUnit\Framework\TestCase;

class RefundTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('return_refund', RefundType::ReturnRefund->value);
        $this->assertSame('only_refund', RefundType::OnlyRefund->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(RefundType::ReturnRefund, RefundType::from('return_refund'));
        $this->assertSame(RefundType::OnlyRefund, RefundType::from('only_refund'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(RefundType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, RefundType::cases());
    }

    public function test_return_refund_label_and_color(): void
    {
        $this->assertSame('退货退款', RefundType::ReturnRefund->getLabel());
        $this->assertSame('orange', RefundType::ReturnRefund->getColor());
    }

    public function test_only_refund_label_and_color(): void
    {
        $this->assertSame('仅退款', RefundType::OnlyRefund->getLabel());
        $this->assertSame('blue', RefundType::OnlyRefund->getColor());
    }
}
