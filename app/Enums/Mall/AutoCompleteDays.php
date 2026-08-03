<?php

namespace App\Enums\Mall;

use Filament\Support\Contracts\HasLabel;

enum AutoCompleteDays: int implements HasLabel
{
    case Days3 = 3;

    case Days7 = 7;

    case Days14 = 14;

    case Days30 = 30;

    public function getLabel(): string
    {
        return match ($this) {
            self::Days3 => '3天自动完成',
            self::Days7 => '7天自动完成',
            self::Days14 => '14天自动完成',
            self::Days30 => '30天自动完成',
        };
    }
}
