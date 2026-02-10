<?php

namespace App\Enums;

use App\Contracts\AssetInterface;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AccountAssetType: string implements HasLabel, HasColor, AssetInterface
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

    public function getField(): string
    {
        return match ($this) {
            self::Balance => 'balance',
            self::Points => 'points',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Balance => 'primary',
            self::Points => 'success',
        };
    }
}
