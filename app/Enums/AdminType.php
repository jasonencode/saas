<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AdminType: string implements HasColor, HasLabel
{
    case Admin = 'admin';

    case Tenant = 'tenant';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => '管理员',
            self::Tenant => '租户',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'success',
            self::Tenant => 'warning',
        };
    }
}
