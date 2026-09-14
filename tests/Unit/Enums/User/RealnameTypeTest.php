<?php

namespace Tests\Unit\Enums\User;

use App\Enums\User\RealnameType;
use PHPUnit\Framework\TestCase;

class RealnameTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('personal', RealnameType::Personal->value);
        $this->assertSame('enterprise', RealnameType::Enterprise->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(RealnameType::Personal, RealnameType::from('personal'));
        $this->assertSame(RealnameType::Enterprise, RealnameType::from('enterprise'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(RealnameType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, RealnameType::cases());
    }

    public function test_personal_label_and_color(): void
    {
        $this->assertSame('个人认证', RealnameType::Personal->getLabel());
        $this->assertSame('primary', RealnameType::Personal->getColor());
    }

    public function test_enterprise_label_and_color(): void
    {
        $this->assertSame('企业认证', RealnameType::Enterprise->getLabel());
        $this->assertSame('danger', RealnameType::Enterprise->getColor());
    }
}
