<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RegionLevel: string implements HasLabel
{
    case Province = 'p';

    case City = 'c';

    case District = 'd';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Province => '省级',
            self::City => '市级',
            self::District => '区级',
        };
    }
}
