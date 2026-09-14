<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\FulfillmentType;
use PHPUnit\Framework\TestCase;

class FulfillmentTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('mail', FulfillmentType::Mail->value);
        $this->assertSame('pickup', FulfillmentType::Pickup->value);
        $this->assertSame('virtual', FulfillmentType::Virtual->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(FulfillmentType::Mail, FulfillmentType::from('mail'));
        $this->assertSame(FulfillmentType::Pickup, FulfillmentType::from('pickup'));
        $this->assertSame(FulfillmentType::Virtual, FulfillmentType::from('virtual'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(FulfillmentType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(3, FulfillmentType::cases());
    }

    public function test_mail_label_and_color(): void
    {
        $this->assertSame('快递邮寄', FulfillmentType::Mail->getLabel());
        $this->assertSame('info', FulfillmentType::Mail->getColor());
    }

    public function test_pickup_label_and_color(): void
    {
        $this->assertSame('门店自提', FulfillmentType::Pickup->getLabel());
        $this->assertSame('warning', FulfillmentType::Pickup->getColor());
    }

    public function test_virtual_label_and_color(): void
    {
        $this->assertSame('虚拟商品', FulfillmentType::Virtual->getLabel());
        $this->assertSame('success', FulfillmentType::Virtual->getColor());
    }
}
