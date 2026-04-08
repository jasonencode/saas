<?php

namespace App\Enums\Campaign;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum ExpiredType: string implements HasColor, HasDescription, HasLabel
{
    case Receive = 'receive';

    case Fixed = 'fixed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fixed => '固定期限',
            self::Receive => '领取后生效',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Fixed => 'primary',
            self::Receive => 'success',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Fixed => '所有优惠券统一起始时间',
            self::Receive => '从领取后生效，有效期自行设置',
        };
    }
}
