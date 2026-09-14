<?php

namespace Tests\Unit\Enums\System;

use App\Enums\System\PolicyType;
use PHPUnit\Framework\TestCase;

class PolicyTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('page', PolicyType::Page->value);
        $this->assertSame('button', PolicyType::Button->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(PolicyType::Page, PolicyType::from('page'));
        $this->assertSame(PolicyType::Button, PolicyType::from('button'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(PolicyType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(2, PolicyType::cases());
    }

    public function test_page_label(): void
    {
        $this->assertSame('页面', PolicyType::Page->getLabel());
    }

    public function test_button_label(): void
    {
        $this->assertSame('按钮', PolicyType::Button->getLabel());
    }
}
