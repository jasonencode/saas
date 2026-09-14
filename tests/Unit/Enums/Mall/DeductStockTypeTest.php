<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\DeductStockType;
use PHPUnit\Framework\TestCase;

class DeductStockTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('ordered', DeductStockType::Ordered->value);
        $this->assertSame('paid', DeductStockType::Paid->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(DeductStockType::Ordered, DeductStockType::from('ordered'));
        $this->assertSame(DeductStockType::Paid, DeductStockType::from('paid'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(DeductStockType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, DeductStockType::cases());
    }

    public function test_ordered_label(): void
    {
        $this->assertSame('下单减库存', DeductStockType::Ordered->getLabel());
    }

    public function test_paid_label(): void
    {
        $this->assertSame('付款减库存', DeductStockType::Paid->getLabel());
    }
}
