<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserAccountLogType: string implements HasLabel
{
    case System = 'system';
    case Recharge = 'recharge';
    case Consume = 'consume';
    case Refund = 'refund';
    case Reward = 'reward';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::System => '系统调整',
            self::Recharge => '充值',
            self::Consume => '消费',
            self::Refund => '退款',
            self::Reward => '奖励',
        };
    }
}
