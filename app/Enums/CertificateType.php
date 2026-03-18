<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CertificateType: string implements HasLabel, HasColor
{
    case CA = 'ca';

    case Intermediate = 'intermediate';

    case Certificate = 'certificate';

    public function getLabel(): string
    {
        return match ($this) {
            self::CA => '根证书',
            self::Intermediate => '中间证书',
            self::Certificate => '客户端证书',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CA => 'danger',
            self::Intermediate => 'warning',
            self::Certificate => 'success',
        };
    }
}
