<?php

namespace App\Enums\System;

use Filament\Support\Contracts\HasLabel;

enum PolicyPlatform: int implements HasLabel
{
    case Backend = 1;

    case Tenant = 2;

    case Both = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::Backend => '总后台',
            self::Tenant => '租户后台',
            self::Both => '全部平台',
        };
    }

    public static function parseLabel(int $value): string
    {
        if ($value === self::Both->value) {
            return self::Both->getLabel();
        }

        $labels = [];

        foreach (self::cases() as $case) {
            if ($case === self::Both) {
                continue;
            }

            if ($value & $case->value) {
                $labels[] = $case->getLabel();
            }
        }

        return implode(', ', $labels);
    }
}
