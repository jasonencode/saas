<?php

namespace Tests\Unit\Enums\System;

use App\Enums\System\AvailableModule;
use PHPUnit\Framework\TestCase;

class AvailableModuleTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('mall', AvailableModule::Mall->value);
        $this->assertSame('content', AvailableModule::Content->value);
        $this->assertSame('campaign', AvailableModule::Campaign->value);
        $this->assertSame('finance', AvailableModule::Finance->value);
        $this->assertSame('user', AvailableModule::User->value);
        $this->assertSame('foundation', AvailableModule::Foundation->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(AvailableModule::Mall, AvailableModule::from('mall'));
        $this->assertSame(AvailableModule::Content, AvailableModule::from('content'));
        $this->assertSame(AvailableModule::Campaign, AvailableModule::from('campaign'));
        $this->assertSame(AvailableModule::Finance, AvailableModule::from('finance'));
        $this->assertSame(AvailableModule::User, AvailableModule::from('user'));
        $this->assertSame(AvailableModule::Foundation, AvailableModule::from('foundation'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(AvailableModule::tryFrom('invalid'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(6, AvailableModule::cases());
    }

    public function test_mall_label_and_color(): void
    {
        $this->assertSame('商城', AvailableModule::Mall->getLabel());
        $this->assertSame('success', AvailableModule::Mall->getColor());
    }

    public function test_content_label_and_color(): void
    {
        $this->assertSame('内容', AvailableModule::Content->getLabel());
        $this->assertSame('info', AvailableModule::Content->getColor());
    }

    public function test_campaign_label_and_color(): void
    {
        $this->assertSame('活动', AvailableModule::Campaign->getLabel());
        $this->assertSame('warning', AvailableModule::Campaign->getColor());
    }

    public function test_finance_label_and_color(): void
    {
        $this->assertSame('财务', AvailableModule::Finance->getLabel());
        $this->assertSame('primary', AvailableModule::Finance->getColor());
    }

    public function test_user_label_and_color(): void
    {
        $this->assertSame('用户', AvailableModule::User->getLabel());
        $this->assertSame('primary', AvailableModule::User->getColor());
    }

    public function test_foundation_label_and_color(): void
    {
        $this->assertSame('基础设施', AvailableModule::Foundation->getLabel());
        $this->assertSame('gray', AvailableModule::Foundation->getColor());
    }
}
