<?php

namespace Tests\Unit\Enums\Finance;

use App\Enums\Finance\InvoiceTitleType;
use PHPUnit\Framework\TestCase;

class InvoiceTitleTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('personal', InvoiceTitleType::Personal->value);
        $this->assertSame('enterprise', InvoiceTitleType::Enterprise->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(InvoiceTitleType::Personal, InvoiceTitleType::from('personal'));
        $this->assertSame(InvoiceTitleType::Enterprise, InvoiceTitleType::from('enterprise'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(InvoiceTitleType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, InvoiceTitleType::cases());
    }

    public function test_personal_label_and_color(): void
    {
        $this->assertSame('个人', InvoiceTitleType::Personal->getLabel());
        $this->assertSame('success', InvoiceTitleType::Personal->getColor());
    }

    public function test_enterprise_label_and_color(): void
    {
        $this->assertSame('企业', InvoiceTitleType::Enterprise->getLabel());
        $this->assertSame('danger', InvoiceTitleType::Enterprise->getColor());
    }
}
