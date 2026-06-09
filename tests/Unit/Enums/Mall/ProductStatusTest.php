<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\ProductStatus;
use PHPUnit\Framework\TestCase;

class ProductStatusTest extends TestCase
{
    public function test_get_label(): void
    {
        $this->assertSame('审核中', ProductStatus::Pending->getLabel());
        $this->assertSame('上架中', ProductStatus::Up->getLabel());
        $this->assertSame('被驳回', ProductStatus::Rejected->getLabel());
        $this->assertSame('已下架', ProductStatus::Down->getLabel());
    }

    public function test_get_color(): void
    {
        $this->assertSame('amber', ProductStatus::Pending->getColor());
        $this->assertSame('emerald', ProductStatus::Up->getColor());
        $this->assertSame('red', ProductStatus::Rejected->getColor());
        $this->assertSame('slate', ProductStatus::Down->getColor());
    }

    public function test_all_cases_have_labels(): void
    {
        foreach (ProductStatus::cases() as $case) {
            $this->assertNotEmpty($case->getLabel());
            $this->assertNotEmpty($case->getColor());
        }
    }
}
