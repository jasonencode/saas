<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RefundStatus: string implements HasLabel, HasColor
{
    case Pending = 'pending';

    case Processing = 'processing';

    case Completed = 'completed';

    case Rejected = 'rejected';

    case Cancelled = 'cancelled';

    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '退款请求已提交，等待处理',
            self::Processing => '退款正在处理中',
            self::Completed => '退款操作已完成',
            self::Rejected => '退款请求被拒绝',
            self::Cancelled => '退款请求被取消',
            self::Failed => '退款操作失败',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Processing => 'sky',
            self::Completed => 'emerald',
            self::Rejected => 'red',
            self::Cancelled => 'rose',
            self::Failed => 'orange',
        };
    }
}
