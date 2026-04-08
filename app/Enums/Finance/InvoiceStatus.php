<?php

namespace App\Enums\Finance;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case Issued = 'issued';

    case Sent = 'sent';

    public function getLabel(): string
    {
        return match ($this) {
            self::Issued => '已开具',
            self::Sent => '已发送',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Issued => 'success',
            self::Sent => 'info',
        };
    }
}
