<?php

namespace App\Enums\Mall;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RegionLevel: string implements HasColor, HasLabel
{
    case Province = 'p';

    case City = 'c';

    case District = 'd';

    public function getLabel(): string
    {
        return match ($this) {
            self::Province => '省级',
            self::City => '市级',
            self::District => '区级',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Province => 'primary',
            self::City => 'success',
            self::District => 'warning',
        };
    }
}
