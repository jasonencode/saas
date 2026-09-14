<?php

namespace Tests\Unit\Enums\Content;

use App\Enums\Content\SuggestStatus;
use PHPUnit\Framework\TestCase;

class SuggestStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', SuggestStatus::Pending->value);
        $this->assertSame('resolved', SuggestStatus::Resolved->value);
        $this->assertSame('closed', SuggestStatus::Closed->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(SuggestStatus::Pending, SuggestStatus::from('pending'));
        $this->assertSame(SuggestStatus::Resolved, SuggestStatus::from('resolved'));
        $this->assertSame(SuggestStatus::Closed, SuggestStatus::from('closed'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(SuggestStatus::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(3, SuggestStatus::cases());
    }

    public function test_pending_label_and_color(): void
    {
        $this->assertSame('待处理', SuggestStatus::Pending->getLabel());
        $this->assertSame('warning', SuggestStatus::Pending->getColor());
    }

    public function test_resolved_label_and_color(): void
    {
        $this->assertSame('已回复', SuggestStatus::Resolved->getLabel());
        $this->assertSame('success', SuggestStatus::Resolved->getColor());
    }

    public function test_closed_label_and_color(): void
    {
        $this->assertSame('已关闭', SuggestStatus::Closed->getLabel());
        $this->assertSame('gray', SuggestStatus::Closed->getColor());
    }
}
