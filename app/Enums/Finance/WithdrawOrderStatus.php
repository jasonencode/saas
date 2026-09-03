<?php

namespace App\Enums\Finance;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WithdrawOrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';

    case Approved = 'approved';

    case Processing = 'processing';

    case Completed = 'completed';

    case Rejected = 'rejected';

    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待审核',
            self::Approved => '审核通过',
            self::Processing => '打款中',
            self::Completed => '已完成',
            self::Rejected => '已拒绝',
            self::Cancelled => '已取消',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'info',
            self::Processing => 'sky',
            self::Completed => 'emerald',
            self::Rejected => 'red',
            self::Cancelled => 'rose',
        };
    }
}
