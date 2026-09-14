<?php

namespace Tests\Unit\Enums\Finance;

use App\Enums\Finance\WithdrawOrderStatus;
use PHPUnit\Framework\TestCase;

class WithdrawOrderStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', WithdrawOrderStatus::Pending->value);
        $this->assertSame('approved', WithdrawOrderStatus::Approved->value);
        $this->assertSame('processing', WithdrawOrderStatus::Processing->value);
        $this->assertSame('completed', WithdrawOrderStatus::Completed->value);
        $this->assertSame('rejected', WithdrawOrderStatus::Rejected->value);
        $this->assertSame('cancelled', WithdrawOrderStatus::Cancelled->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(WithdrawOrderStatus::Pending, WithdrawOrderStatus::from('pending'));
        $this->assertSame(WithdrawOrderStatus::Approved, WithdrawOrderStatus::from('approved'));
        $this->assertSame(WithdrawOrderStatus::Processing, WithdrawOrderStatus::from('processing'));
        $this->assertSame(WithdrawOrderStatus::Completed, WithdrawOrderStatus::from('completed'));
        $this->assertSame(WithdrawOrderStatus::Rejected, WithdrawOrderStatus::from('rejected'));
        $this->assertSame(WithdrawOrderStatus::Cancelled, WithdrawOrderStatus::from('cancelled'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(WithdrawOrderStatus::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(6, WithdrawOrderStatus::cases());
    }

    public function test_pending_label_and_color(): void
    {
        $this->assertSame('待审核', WithdrawOrderStatus::Pending->getLabel());
        $this->assertSame('amber', WithdrawOrderStatus::Pending->getColor());
    }

    public function test_approved_label_and_color(): void
    {
        $this->assertSame('审核通过', WithdrawOrderStatus::Approved->getLabel());
        $this->assertSame('info', WithdrawOrderStatus::Approved->getColor());
    }

    public function test_processing_label_and_color(): void
    {
        $this->assertSame('打款中', WithdrawOrderStatus::Processing->getLabel());
        $this->assertSame('sky', WithdrawOrderStatus::Processing->getColor());
    }

    public function test_completed_label_and_color(): void
    {
        $this->assertSame('已完成', WithdrawOrderStatus::Completed->getLabel());
        $this->assertSame('emerald', WithdrawOrderStatus::Completed->getColor());
    }

    public function test_rejected_label_and_color(): void
    {
        $this->assertSame('已拒绝', WithdrawOrderStatus::Rejected->getLabel());
        $this->assertSame('red', WithdrawOrderStatus::Rejected->getColor());
    }

    public function test_cancelled_label_and_color(): void
    {
        $this->assertSame('已取消', WithdrawOrderStatus::Cancelled->getLabel());
        $this->assertSame('rose', WithdrawOrderStatus::Cancelled->getColor());
    }
}
