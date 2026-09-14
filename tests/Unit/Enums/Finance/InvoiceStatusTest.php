<?php

namespace Tests\Unit\Enums\Finance;

use App\Enums\Finance\InvoiceStatus;
use PHPUnit\Framework\TestCase;

class InvoiceStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('issued', InvoiceStatus::Issued->value);
        $this->assertSame('sent', InvoiceStatus::Sent->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(InvoiceStatus::Issued, InvoiceStatus::from('issued'));
        $this->assertSame(InvoiceStatus::Sent, InvoiceStatus::from('sent'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(InvoiceStatus::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, InvoiceStatus::cases());
    }

    public function test_issued_label_and_color(): void
    {
        $this->assertSame('已开具', InvoiceStatus::Issued->getLabel());
        $this->assertSame('success', InvoiceStatus::Issued->getColor());
    }

    public function test_sent_label_and_color(): void
    {
        $this->assertSame('已发送', InvoiceStatus::Sent->getLabel());
        $this->assertSame('info', InvoiceStatus::Sent->getColor());
    }
}
