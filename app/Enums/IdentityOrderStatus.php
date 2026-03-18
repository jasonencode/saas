<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IdentityOrderStatus: string implements HasLabel, HasColor
{
    case UNPAY = 'unpay';
    case PAID = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::UNPAY => '未支付',
            self::PAID => '已支付',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::UNPAY => 'primary',
            self::PAID => 'success',
        };
    }
}