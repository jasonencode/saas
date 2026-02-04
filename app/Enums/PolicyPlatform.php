<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PolicyPlatform: int implements HasLabel
{
    case Backend = 1;

    case Tenant = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Backend => '总后台',
            self::Tenant => '租户后台',
        };
    }
}
