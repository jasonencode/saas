<?php

namespace App\Enums\Finance;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RechargeOrderType: string implements HasColor, HasLabel
{
    case Balance = 'balance';

    case Points = 'points';

    public function getLabel(): string
    {
        return match ($this) {
            self::Balance => '余额充值',
            self::Points => '积分充值',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Balance => 'success',
            self::Points => 'warning',
        };
    }
}
