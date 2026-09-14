<?php

namespace Tests\Unit\Enums\Finance;

use App\Enums\Finance\InvoiceType;
use PHPUnit\Framework\TestCase;

class InvoiceTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('normal', InvoiceType::Normal->value);
        $this->assertSame('vat', InvoiceType::Vat->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(InvoiceType::Normal, InvoiceType::from('normal'));
        $this->assertSame(InvoiceType::Vat, InvoiceType::from('vat'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(InvoiceType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, InvoiceType::cases());
    }

    public function test_normal_label_and_color(): void
    {
        $this->assertSame('普通发票', InvoiceType::Normal->getLabel());
        $this->assertSame('primary', InvoiceType::Normal->getColor());
    }

    public function test_vat_label_and_color(): void
    {
        $this->assertSame('增值税发票', InvoiceType::Vat->getLabel());
        $this->assertSame('info', InvoiceType::Vat->getColor());
    }
}
