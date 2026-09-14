<?php

namespace Tests\Unit\Enums\Content;

use App\Enums\Content\SuggestType;
use PHPUnit\Framework\TestCase;

class SuggestTypeTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('feature', SuggestType::Feature->value);
        $this->assertSame('bug', SuggestType::Bug->value);
        $this->assertSame('account', SuggestType::Account->value);
        $this->assertSame('other', SuggestType::Other->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(SuggestType::Feature, SuggestType::from('feature'));
        $this->assertSame(SuggestType::Bug, SuggestType::from('bug'));
        $this->assertSame(SuggestType::Account, SuggestType::from('account'));
        $this->assertSame(SuggestType::Other, SuggestType::from('other'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(SuggestType::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(4, SuggestType::cases());
    }

    public function test_feature_label_and_color(): void
    {
        $this->assertSame('功能建议', SuggestType::Feature->getLabel());
        $this->assertSame('primary', SuggestType::Feature->getColor());
    }

    public function test_bug_label_and_color(): void
    {
        $this->assertSame('问题反馈', SuggestType::Bug->getLabel());
        $this->assertSame('danger', SuggestType::Bug->getColor());
    }

    public function test_account_label_and_color(): void
    {
        $this->assertSame('账号问题', SuggestType::Account->getLabel());
        $this->assertSame('warning', SuggestType::Account->getColor());
    }

    public function test_other_label_and_color(): void
    {
        $this->assertSame('其他', SuggestType::Other->getLabel());
        $this->assertSame('gray', SuggestType::Other->getColor());
    }
}
