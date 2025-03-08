<?php

namespace App\Enums;

use App\Enums\Traits\EnumMethods;
use Filament\Support\Contracts\HasLabel;

enum RegionLevel: string implements HasLabel
{
    use EnumMethods;

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
