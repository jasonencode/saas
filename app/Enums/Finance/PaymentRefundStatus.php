<?php

namespace App\Enums\Finance;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentRefundStatus: string implements HasColor, HasLabel
{
    /**
     * 退款申请已提交，等待审核（若无需审核则直接进入处理中）
     */
    case Pending = 'pending';

    /**
     * 审核通过，等待退款执行（可选，若无需审核则跳过）
     */
    case Approved = 'approved';

    /**
     * 退款正在处理中（已调用网关，等待结果）
     */
    case Processing = 'processing';

    /**
     * 退款成功完成
     */
    case Completed = 'completed';

    /**
     * 审核拒绝
     */
    case Rejected = 'rejected';

    /**
     * 用户/系统取消退款申请
     */
    case Cancelled = 'cancelled';

    /**
     * 退款执行失败（网关返回失败）
     */
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待审核',
            self::Approved => '审核通过',
            self::Processing => '退款处理中',
            self::Completed => '退款完成',
            self::Rejected => '已拒绝',
            self::Cancelled => '已取消',
            self::Failed => '退款失败',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Approved => 'sky',
            self::Processing => 'blue',
            self::Completed => 'emerald',
            self::Rejected => 'red',
            self::Cancelled => 'rose',
            self::Failed => 'orange',
        };
    }
}
