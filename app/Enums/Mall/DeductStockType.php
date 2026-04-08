<?php

namespace App\Enums\Mall;

use Filament\Support\Contracts\HasLabel;

enum DeductStockType: string implements HasLabel
{
    case Ordered = 'ordered';

    case Paid = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ordered => '下单减库存',
            self::Paid => '付款减库存',
        };
    }
}
