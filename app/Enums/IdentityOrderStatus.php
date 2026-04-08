<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IdentityOrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';

    case Paid = 'paid';

    case Refunded = 'refund';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '未支付',
            self::Paid => '已支付',
            self::Refunded => '已退款',

        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'primary',
            self::Paid => 'success',
            self::Refunded => 'warning',
        };
    }
}
