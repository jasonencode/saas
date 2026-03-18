<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasLabel, HasColor
{
    case Pending = 'pending';

    case Paid = 'paid';

    case Failed = 'failed';

    case Cancelled = 'cancelled';

    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待支付',
            self::Paid => '已支付',
            self::Failed => '支付失败',
            self::Cancelled => '已取消',
            self::Refunded => '已退款',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'gray',
            self::Refunded => 'danger',
        };
    }
}
