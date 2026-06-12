<?php

namespace App\Enums\Campaign;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LotteryPrizeType: string implements HasColor, HasLabel
{
    case Balance = 'balance';

    case Points = 'points';

    case Coupon = 'coupon';

    case Redpack = 'redpack';

    case Physical = 'physical';

    case None = 'none';

    public function getLabel(): string
    {
        return match ($this) {
            self::Balance => '余额',
            self::Points => '积分',
            self::Coupon => '优惠券',
            self::Redpack => '红包',
            self::Physical => '实物奖品',
            self::None => '谢谢参与',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Balance => 'success',
            self::Points => 'info',
            self::Coupon => 'primary',
            self::Redpack => 'danger',
            self::Physical => 'warning',
            self::None => 'gray',
        };
    }
}
