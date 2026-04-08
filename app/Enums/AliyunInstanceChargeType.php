<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AliyunInstanceChargeType: string implements HasColor, HasLabel
{
    case PostPaid = 'PostPaid';

    case PrePaid = 'PrePaid';

    public function getLabel(): string
    {
        return match ($this) {
            self::PostPaid => '按量付费',
            self::PrePaid => '包年包月',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PostPaid => 'info',
            self::PrePaid => 'primary',
        };
    }
}
