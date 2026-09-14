<?php

namespace Tests\Unit\Enums\Finance;

use App\Enums\Finance\PaymentRefundStatus;
use PHPUnit\Framework\TestCase;

class PaymentRefundStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', PaymentRefundStatus::Pending->value);
        $this->assertSame('approved', PaymentRefundStatus::Approved->value);
        $this->assertSame('processing', PaymentRefundStatus::Processing->value);
        $this->assertSame('completed', PaymentRefundStatus::Completed->value);
        $this->assertSame('rejected', PaymentRefundStatus::Rejected->value);
        $this->assertSame('cancelled', PaymentRefundStatus::Cancelled->value);
        $this->assertSame('failed', PaymentRefundStatus::Failed->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(PaymentRefundStatus::Pending, PaymentRefundStatus::from('pending'));
        $this->assertSame(PaymentRefundStatus::Approved, PaymentRefundStatus::from('approved'));
        $this->assertSame(PaymentRefundStatus::Processing, PaymentRefundStatus::from('processing'));
        $this->assertSame(PaymentRefundStatus::Completed, PaymentRefundStatus::from('completed'));
        $this->assertSame(PaymentRefundStatus::Rejected, PaymentRefundStatus::from('rejected'));
        $this->assertSame(PaymentRefundStatus::Cancelled, PaymentRefundStatus::from('cancelled'));
        $this->assertSame(PaymentRefundStatus::Failed, PaymentRefundStatus::from('failed'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(PaymentRefundStatus::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(7, PaymentRefundStatus::cases());
    }

    public function test_pending_label_and_color(): void
    {
        $this->assertSame('待审核', PaymentRefundStatus::Pending->getLabel());
        $this->assertSame('amber', PaymentRefundStatus::Pending->getColor());
    }

    public function test_approved_label_and_color(): void
    {
        $this->assertSame('审核通过', PaymentRefundStatus::Approved->getLabel());
        $this->assertSame('sky', PaymentRefundStatus::Approved->getColor());
    }

    public function test_processing_label_and_color(): void
    {
        $this->assertSame('退款处理中', PaymentRefundStatus::Processing->getLabel());
        $this->assertSame('blue', PaymentRefundStatus::Processing->getColor());
    }

    public function test_completed_label_and_color(): void
    {
        $this->assertSame('退款完成', PaymentRefundStatus::Completed->getLabel());
        $this->assertSame('emerald', PaymentRefundStatus::Completed->getColor());
    }

    public function test_rejected_label_and_color(): void
    {
        $this->assertSame('已拒绝', PaymentRefundStatus::Rejected->getLabel());
        $this->assertSame('red', PaymentRefundStatus::Rejected->getColor());
    }

    public function test_cancelled_label_and_color(): void
    {
        $this->assertSame('已取消', PaymentRefundStatus::Cancelled->getLabel());
        $this->assertSame('rose', PaymentRefundStatus::Cancelled->getColor());
    }

    public function test_failed_label_and_color(): void
    {
        $this->assertSame('退款失败', PaymentRefundStatus::Failed->getLabel());
        $this->assertSame('orange', PaymentRefundStatus::Failed->getColor());
    }
}
