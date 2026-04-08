<?php

namespace App\Enums\Finance;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceTitleType: string implements HasColor, HasLabel
{
    case Personal = 'personal';
    case Enterprise = 'enterprise';

    public function getLabel(): string
    {
        return match ($this) {
            self::Personal => '个人',
            self::Enterprise => '企业',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Personal => 'success',
            self::Enterprise => 'danger',
        };
    }
}
