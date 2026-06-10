<?php

namespace App\Enums\Campaign;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LotteryPrizeStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';

    case Fulfilled = 'fulfilled';

    case Expired = 'expired';

    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待兑奖',
            self::Fulfilled => '已兑奖',
            self::Expired => '已过期',
            self::Cancelled => '已取消',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Fulfilled => 'success',
            self::Expired => 'gray',
            self::Cancelled => 'danger',
        };
    }
}
