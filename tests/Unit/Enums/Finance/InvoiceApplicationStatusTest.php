<?php

namespace Tests\Unit\Enums\Finance;

use App\Enums\Finance\InvoiceApplicationStatus;
use PHPUnit\Framework\TestCase;

class InvoiceApplicationStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', InvoiceApplicationStatus::Pending->value);
        $this->assertSame('approved', InvoiceApplicationStatus::Approved->value);
        $this->assertSame('rejected', InvoiceApplicationStatus::Rejected->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(InvoiceApplicationStatus::Pending, InvoiceApplicationStatus::from('pending'));
        $this->assertSame(InvoiceApplicationStatus::Approved, InvoiceApplicationStatus::from('approved'));
        $this->assertSame(InvoiceApplicationStatus::Rejected, InvoiceApplicationStatus::from('rejected'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(InvoiceApplicationStatus::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(3, InvoiceApplicationStatus::cases());
    }

    public function test_pending_label_and_color(): void
    {
        $this->assertSame('待处理', InvoiceApplicationStatus::Pending->getLabel());
        $this->assertSame('warning', InvoiceApplicationStatus::Pending->getColor());
    }

    public function test_approved_label_and_color(): void
    {
        $this->assertSame('已批准', InvoiceApplicationStatus::Approved->getLabel());
        $this->assertSame('success', InvoiceApplicationStatus::Approved->getColor());
    }

    public function test_rejected_label_and_color(): void
    {
        $this->assertSame('已拒绝', InvoiceApplicationStatus::Rejected->getLabel());
        $this->assertSame('danger', InvoiceApplicationStatus::Rejected->getColor());
    }
}
