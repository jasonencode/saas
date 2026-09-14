<?php

namespace Tests\Unit\Enums\Mall;

use App\Enums\Mall\ApplyStatus;
use PHPUnit\Framework\TestCase;

class ApplyStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', ApplyStatus::Pending->value);
        $this->assertSame('approved', ApplyStatus::Approved->value);
        $this->assertSame('rejected', ApplyStatus::Rejected->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(ApplyStatus::Pending, ApplyStatus::from('pending'));
        $this->assertSame(ApplyStatus::Approved, ApplyStatus::from('approved'));
        $this->assertSame(ApplyStatus::Rejected, ApplyStatus::from('rejected'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(ApplyStatus::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(3, ApplyStatus::cases());
    }

    public function test_pending_label_and_color(): void
    {
        $this->assertSame('申请中', ApplyStatus::Pending->getLabel());
        $this->assertSame('primary', ApplyStatus::Pending->getColor());
    }

    public function test_approved_label_and_color(): void
    {
        $this->assertSame('已批准', ApplyStatus::Approved->getLabel());
        $this->assertSame('success', ApplyStatus::Approved->getColor());
    }

    public function test_rejected_label_and_color(): void
    {
        $this->assertSame('已拒绝', ApplyStatus::Rejected->getLabel());
        $this->assertSame('danger', ApplyStatus::Rejected->getColor());
    }
}
