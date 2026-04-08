<?php

namespace App\Enums\Finance;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceType: string implements HasColor, HasLabel
{
    case Normal = 'normal';

    case Vat = 'vat';

    public function getLabel(): string
    {
        return match ($this) {
            self::Normal => '普通发票',
            self::Vat => '增值税发票',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Normal => 'primary',
            self::Vat => 'info',
        };
    }
}
