<?php

namespace App\Enums\User;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserAccountLogType: string implements HasColor, HasLabel
{
    case System = 'system';

    case Recharge = 'recharge';

    case Consume = 'consume';

    case Refund = 'refund';

    case Reward = 'reward';

    case Freeze = 'freeze';

    case Unfreeze = 'unfreeze';

    public function getLabel(): string
    {
        return match ($this) {
            self::System => '系统调整',
            self::Recharge => '充值',
            self::Consume => '消费',
            self::Refund => '退款',
            self::Reward => '奖励',
            self::Freeze => '冻结',
            self::Unfreeze => '解冻',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::System => 'neutral',
            self::Recharge => 'emerald',
            self::Consume => 'rose',
            self::Refund => 'teal',
            self::Reward => 'amber',
            self::Freeze => 'red',
            self::Unfreeze => 'sky',
        };
    }
}
