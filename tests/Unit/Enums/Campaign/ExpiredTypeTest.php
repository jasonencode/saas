<?php

namespace Tests\Unit\Enums\Campaign;

use App\Enums\Campaign\ExpiredType;
use PHPUnit\Framework\TestCase;

class ExpiredTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('receive', ExpiredType::Receive->value);
        $this->assertSame('fixed', ExpiredType::Fixed->value);
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, ExpiredType::cases());
    }

    public function test_fixed_label_and_color(): void
    {
        $this->assertSame('固定期限', ExpiredType::Fixed->getLabel());
        $this->assertSame('primary', ExpiredType::Fixed->getColor());
    }

    public function test_receive_label_and_color(): void
    {
        $this->assertSame('领取后生效', ExpiredType::Receive->getLabel());
        $this->assertSame('success', ExpiredType::Receive->getColor());
    }

    public function test_fixed_description(): void
    {
        $this->assertSame('所有优惠券统一起始时间', ExpiredType::Fixed->getDescription());
    }

    public function test_receive_description(): void
    {
        $this->assertSame('从领取后生效，有效期自行设置', ExpiredType::Receive->getDescription());
    }
}
