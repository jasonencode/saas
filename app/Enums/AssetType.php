<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AssetType: string implements HasLabel
{
    case Balance = 'balance';
    case Points = 'points';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Balance => '余额',
            self::Points => '积分',
        };
    }
}
