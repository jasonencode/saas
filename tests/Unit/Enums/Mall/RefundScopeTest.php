<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\RefundScope;
use PHPUnit\Framework\TestCase;

class RefundScopeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', RefundScope::Pending->value);
        $this->assertSame('processing', RefundScope::Processing->value);
        $this->assertSame('completed', RefundScope::Completed->value);
        $this->assertSame('closed', RefundScope::Closed->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(RefundScope::Pending, RefundScope::from('pending'));
        $this->assertSame(RefundScope::Processing, RefundScope::from('processing'));
        $this->assertSame(RefundScope::Completed, RefundScope::from('completed'));
        $this->assertSame(RefundScope::Closed, RefundScope::from('closed'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(RefundScope::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(4, RefundScope::cases());
    }

    public function test_pending_label_and_color(): void
    {
        $this->assertSame('待审核', RefundScope::Pending->getLabel());
        $this->assertSame('warning', RefundScope::Pending->getColor());
    }

    public function test_processing_label_and_color(): void
    {
        $this->assertSame('处理中', RefundScope::Processing->getLabel());
        $this->assertSame('primary', RefundScope::Processing->getColor());
    }

    public function test_completed_label_and_color(): void
    {
        $this->assertSame('已完成', RefundScope::Completed->getLabel());
        $this->assertSame('success', RefundScope::Completed->getColor());
    }

    public function test_closed_label_and_color(): void
    {
        $this->assertSame('已关闭', RefundScope::Closed->getLabel());
        $this->assertSame('gray', RefundScope::Closed->getColor());
    }
}
