<?php

namespace App\Enums\Campaign;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RedpackCodeStatus: string implements HasColor, HasLabel
{
    case Active = 'active';

    case Claimed = 'claimed';

    case Disabled = 'disabled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => '待领取',
            self::Claimed => '已领取',
            self::Disabled => '禁用',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'primary',
            self::Claimed => 'success',
            self::Disabled => 'warning',
        };
    }
}
