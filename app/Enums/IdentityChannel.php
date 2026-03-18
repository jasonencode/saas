<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IdentityChannel: string implements HasLabel, HasColor
{
    case AUTO = 'Auto';
    case REG = 'Reg';
    case SUBSCRIBE = 'Subscribe';
    case SYSTEM = 'System';
    case CARD = 'Card';

    public function getLabel(): string
    {
        return match ($this) {
            self::AUTO => '自动变更',
            self::REG => '注册默认',
            self::SUBSCRIBE => '付费订阅',
            self::SYSTEM => '后台变更',
            self::CARD => '会员卡激活',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::AUTO => 'info',
            self::REG => 'default',
            self::SUBSCRIBE => 'danger',
            self::SYSTEM => 'warning',
            self::CARD => 'success',
        };
    }
}