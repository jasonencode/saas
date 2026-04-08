<?php

namespace App\Enums\User;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IdentityChannel: string implements HasColor, HasLabel
{
    case Auto = 'Auto';

    case Reg = 'Reg';

    case Subscribe = 'Subscribe';

    case System = 'System';

    case Card = 'Card';

    public function getLabel(): string
    {
        return match ($this) {
            self::Auto => '自动变更',
            self::Reg => '注册默认',
            self::Subscribe => '付费订阅',
            self::System => '后台变更',
            self::Card => '会员卡激活',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Auto => 'sky',
            self::Reg => 'emerald',
            self::Subscribe => 'purple',
            self::System => 'slate',
            self::Card => 'amber',
        };
    }
}
