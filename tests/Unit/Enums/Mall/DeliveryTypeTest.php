<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\DeliveryType;
use PHPUnit\Framework\TestCase;

class DeliveryTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('weight', DeliveryType::Weight->value);
        $this->assertSame('count', DeliveryType::Count->value);
        $this->assertSame('size', DeliveryType::Size->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(DeliveryType::Weight, DeliveryType::from('weight'));
        $this->assertSame(DeliveryType::Count, DeliveryType::from('count'));
        $this->assertSame(DeliveryType::Size, DeliveryType::from('size'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(DeliveryType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $cases = DeliveryType::cases();

        $this->assertCount(3, $cases);
    }

    public function test_weight_label(): void
    {
        $this->assertSame('按重量', DeliveryType::Weight->getLabel());
    }

    public function test_count_label(): void
    {
        $this->assertSame('按数量', DeliveryType::Count->getLabel());
    }

    public function test_size_label(): void
    {
        $this->assertSame('按体积', DeliveryType::Size->getLabel());
    }
}
