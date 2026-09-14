<?php

namespace Tests\Unit\Enums\System;

use App\Enums\System\LogLevel;
use PHPUnit\Framework\TestCase;

class LogLevelTest extends TestCase
{
    public function test_enum_has_correct_values(): void
    {
        $this->assertSame('DEBUG', LogLevel::DEBUG->value);
        $this->assertSame('INFO', LogLevel::INFO->value);
        $this->assertSame('NOTICE', LogLevel::NOTICE->value);
        $this->assertSame('WARNING', LogLevel::WARNING->value);
        $this->assertSame('ERROR', LogLevel::ERROR->value);
        $this->assertSame('CRITICAL', LogLevel::CRITICAL->value);
        $this->assertSame('ALERT', LogLevel::ALERT->value);
        $this->assertSame('EMERGENCY', LogLevel::EMERGENCY->value);
    }

    public function test_enum_from_string(): void
    {
        $this->assertSame(LogLevel::DEBUG, LogLevel::from('DEBUG'));
        $this->assertSame(LogLevel::INFO, LogLevel::from('INFO'));
        $this->assertSame(LogLevel::NOTICE, LogLevel::from('NOTICE'));
        $this->assertSame(LogLevel::WARNING, LogLevel::from('WARNING'));
        $this->assertSame(LogLevel::ERROR, LogLevel::from('ERROR'));
        $this->assertSame(LogLevel::CRITICAL, LogLevel::from('CRITICAL'));
        $this->assertSame(LogLevel::ALERT, LogLevel::from('ALERT'));
        $this->assertSame(LogLevel::EMERGENCY, LogLevel::from('EMERGENCY'));
    }

    public function test_enum_try_from_invalid_returns_null(): void
    {
        $this->assertNull(LogLevel::tryFrom('INVALID'));
    }

    public function test_enum_has_all_cases(): void
    {
        $this->assertCount(8, LogLevel::cases());
    }

    public function test_debug_label_and_color(): void
    {
        $this->assertSame('调试', LogLevel::DEBUG->getLabel());
        $this->assertSame('gray', LogLevel::DEBUG->getColor());
    }

    public function test_info_label_and_color(): void
    {
        $this->assertSame('信息', LogLevel::INFO->getLabel());
        $this->assertSame('info', LogLevel::INFO->getColor());
    }

    public function test_notice_label_and_color(): void
    {
        $this->assertSame('通知', LogLevel::NOTICE->getLabel());
        $this->assertSame('info', LogLevel::NOTICE->getColor());
    }

    public function test_warning_label_and_color(): void
    {
        $this->assertSame('警告', LogLevel::WARNING->getLabel());
        $this->assertSame('warning', LogLevel::WARNING->getColor());
    }

    public function test_error_label_and_color(): void
    {
        $this->assertSame('错误', LogLevel::ERROR->getLabel());
        $this->assertSame('danger', LogLevel::ERROR->getColor());
    }

    public function test_critical_label_and_color(): void
    {
        $this->assertSame('严重', LogLevel::CRITICAL->getLabel());
        $this->assertSame('danger', LogLevel::CRITICAL->getColor());
    }

    public function test_alert_label_and_color(): void
    {
        $this->assertSame('警报', LogLevel::ALERT->getLabel());
        $this->assertSame('danger', LogLevel::ALERT->getColor());
    }

    public function test_emergency_label_and_color(): void
    {
        $this->assertSame('紧急', LogLevel::EMERGENCY->getLabel());
        $this->assertSame('danger', LogLevel::EMERGENCY->getColor());
    }
}
