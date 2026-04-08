<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';

    case Processing = 'processing';

    case Paid = 'paid';

    case Failed = 'failed';

    case Canceled = 'canceled';

    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待支付',
            self::Processing => '支付处理中',
            self::Paid => '已支付',
            self::Failed => '支付失败',
            self::Canceled => '已取消',
            self::Refunded => '已退款',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Processing => 'sky',
            self::Paid => 'emerald',
            self::Failed => 'red',
            self::Canceled => 'rose',
            self::Refunded => 'neutral',
        };
    }
}
