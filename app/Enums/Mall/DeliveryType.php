<?php

namespace App\Enums\Mall;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeliveryType: string implements HasColor, HasLabel
{
    case Weight = 'weight';

    case Count = 'count';

    case Size = 'size';

    public function getLabel(): string
    {
        return match ($this) {
            self::Weight => '按重量',
            self::Count => '按数量',
            self::Size => '按体积',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Weight => 'warning',
            self::Count => 'info',
            self::Size => 'success',
        };
    }
}
