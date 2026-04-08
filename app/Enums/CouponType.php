<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CouponType: string implements HasColor, HasLabel
{
    case Fixed = 'fixed';

    case Percent = 'percent';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Fixed => '固定金额',
            self::Percent => '百分比',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Fixed => 'primary',
            self::Percent => 'success',
        };
    }
}
