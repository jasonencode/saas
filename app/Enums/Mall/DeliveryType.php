<?php

namespace App\Enums\Mall;

use Filament\Support\Contracts\HasLabel;

enum DeliveryType: string implements HasLabel
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
}
