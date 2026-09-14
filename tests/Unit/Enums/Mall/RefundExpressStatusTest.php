<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\RefundExpressStatus;
use PHPUnit\Framework\TestCase;

class RefundExpressStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', RefundExpressStatus::Pending->value);
        $this->assertSame('shipped', RefundExpressStatus::Shipped->value);
        $this->assertSame('received', RefundExpressStatus::Received->value);
        $this->assertSame('checked', RefundExpressStatus::Checked->value);
        $this->assertSame('rejected', RefundExpressStatus::Rejected->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(RefundExpressStatus::Pending, RefundExpressStatus::from('pending'));
        $this->assertSame(RefundExpressStatus::Shipped, RefundExpressStatus::from('shipped'));
        $this->assertSame(RefundExpressStatus::Received, RefundExpressStatus::from('received'));
        $this->assertSame(RefundExpressStatus::Checked, RefundExpressStatus::from('checked'));
        $this->assertSame(RefundExpressStatus::Rejected, RefundExpressStatus::from('rejected'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(RefundExpressStatus::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(5, RefundExpressStatus::cases());
    }

    public function test_pending_label_and_color(): void
    {
        $this->assertSame('待发货', RefundExpressStatus::Pending->getLabel());
        $this->assertSame('amber', RefundExpressStatus::Pending->getColor());
    }

    public function test_shipped_label_and_color(): void
    {
        $this->assertSame('已发货', RefundExpressStatus::Shipped->getLabel());
        $this->assertSame('blue', RefundExpressStatus::Shipped->getColor());
    }

    public function test_received_label_and_color(): void
    {
        $this->assertSame('已签收', RefundExpressStatus::Received->getLabel());
        $this->assertSame('teal', RefundExpressStatus::Received->getColor());
    }

    public function test_checked_label_and_color(): void
    {
        $this->assertSame('已验收', RefundExpressStatus::Checked->getLabel());
        $this->assertSame('emerald', RefundExpressStatus::Checked->getColor());
    }

    public function test_rejected_label_and_color(): void
    {
        $this->assertSame('已拒收', RefundExpressStatus::Rejected->getLabel());
        $this->assertSame('red', RefundExpressStatus::Rejected->getColor());
    }
}
