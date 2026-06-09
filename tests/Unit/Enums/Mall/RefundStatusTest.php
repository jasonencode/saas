<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\RefundStatus;
use PHPUnit\Framework\TestCase;

class RefundStatusTest extends TestCase
{
    public function test_get_label(): void
    {
        $this->assertSame('退款请求已提交，等待处理', RefundStatus::Pending->getLabel());
        $this->assertSame('退款正在处理中', RefundStatus::Processing->getLabel());
        $this->assertSame('退款操作已完成', RefundStatus::Completed->getLabel());
        $this->assertSame('退款请求被拒绝', RefundStatus::Rejected->getLabel());
        $this->assertSame('退款请求被取消', RefundStatus::Cancelled->getLabel());
        $this->assertSame('退款操作失败', RefundStatus::Failed->getLabel());
    }

    public function test_get_color(): void
    {
        $this->assertSame('amber', RefundStatus::Pending->getColor());
        $this->assertSame('sky', RefundStatus::Processing->getColor());
        $this->assertSame('emerald', RefundStatus::Completed->getColor());
        $this->assertSame('red', RefundStatus::Rejected->getColor());
        $this->assertSame('rose', RefundStatus::Cancelled->getColor());
        $this->assertSame('orange', RefundStatus::Failed->getColor());
    }

    public function test_all_cases_have_labels_and_colors(): void
    {
        foreach (RefundStatus::cases() as $case) {
            $this->assertNotEmpty($case->getLabel());
            $this->assertNotEmpty($case->getColor());
        }
    }
}
