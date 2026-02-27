<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AliyunDomainStatus: int implements HasLabel, HasColor
{
    case NEED_RENEW = 1;

    case NEED_REDEMPTION = 2;

    case NORMAL = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::NEED_RENEW => '急需续费',
            self::NEED_REDEMPTION => '急需赎回',
            self::NORMAL => '正常',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NEED_RENEW => 'warning',
            self::NEED_REDEMPTION => 'danger',
            self::NORMAL => 'success',
        };
    }
}