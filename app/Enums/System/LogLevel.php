<?php

namespace App\Enums\System;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LogLevel: string implements HasColor, HasLabel
{
    case DEBUG = 'DEBUG';

    case INFO = 'INFO';

    case NOTICE = 'NOTICE';

    case WARNING = 'WARNING';

    case ERROR = 'ERROR';

    case CRITICAL = 'CRITICAL';

    case ALERT = 'ALERT';

    case EMERGENCY = 'EMERGENCY';

    public function getLabel(): string
    {
        return match ($this) {
            self::DEBUG => '调试',
            self::INFO => '信息',
            self::NOTICE => '通知',
            self::WARNING => '警告',
            self::ERROR => '错误',
            self::CRITICAL => '严重',
            self::ALERT => '警报',
            self::EMERGENCY => '紧急',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DEBUG => 'gray',
            self::INFO => 'info',
            self::NOTICE => 'info',
            self::WARNING => 'warning',
            self::ERROR => 'danger',
            self::CRITICAL => 'danger',
            self::ALERT => 'danger',
            self::EMERGENCY => 'danger',
        };
    }
}
