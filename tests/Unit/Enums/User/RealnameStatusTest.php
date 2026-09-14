<?php

namespace Tests\Unit\Enums\User;

use App\Enums\User\RealnameStatus;
use PHPUnit\Framework\TestCase;

class RealnameStatusTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('pending', RealnameStatus::Pending->value);
        $this->assertSame('approved', RealnameStatus::Approved->value);
        $this->assertSame('rejected', RealnameStatus::Rejected->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(RealnameStatus::Pending, RealnameStatus::from('pending'));
        $this->assertSame(RealnameStatus::Approved, RealnameStatus::from('approved'));
        $this->assertSame(RealnameStatus::Rejected, RealnameStatus::from('rejected'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(RealnameStatus::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(3, RealnameStatus::cases());
    }

    public function test_pending_label_and_color(): void
    {
        $this->assertSame('待审核', RealnameStatus::Pending->getLabel());
        $this->assertSame('warning', RealnameStatus::Pending->getColor());
    }

    public function test_approved_label_and_color(): void
    {
        $this->assertSame('已认证', RealnameStatus::Approved->getLabel());
        $this->assertSame('success', RealnameStatus::Approved->getColor());
    }

    public function test_rejected_label_and_color(): void
    {
        $this->assertSame('已拒绝', RealnameStatus::Rejected->getLabel());
        $this->assertSame('danger', RealnameStatus::Rejected->getColor());
    }
}
