<?php

namespace App\Enums\Campaign;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum LotteryDrawMode: string implements HasColor, HasDescription, HasLabel
{
    case Free = 'free';

    case Points = 'points';

    public function getLabel(): string
    {
        return match ($this) {
            self::Free => '免费抽奖',
            self::Points => '积分抽奖',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Free => 'success',
            self::Points => 'warning',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Free => '每日免费N次',
            self::Points => '每次消耗X积分',
        };
    }
}
