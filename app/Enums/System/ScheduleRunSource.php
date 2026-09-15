<?php

namespace App\Enums\System;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ScheduleRunSource: string implements HasColor, HasLabel
{
    case Schedule = 'schedule';

    case Manual = 'manual';

    public function getLabel(): string
    {
        return match ($this) {
            self::Schedule => '自动调度',
            self::Manual => '手动执行',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Schedule => 'primary',
            self::Manual => 'warning',
        };
    }
}
