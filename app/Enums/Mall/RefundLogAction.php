<?php

namespace App\Enums\Mall;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RefundLogAction: string implements HasColor, HasLabel
{
    /**
     * 创建退款
     */
    case Created = 'created';

    /**
     * 审核通过
     */
    case Approved = 'approved';

    /**
     * 审核拒绝
     */
    case Rejected = 'rejected';

    /**
     * 取消退款
     */
    case Cancelled = 'cancelled';

    /**
     * 等待退货
     */
    case WaitingReturn = 'waiting_return';

    /**
     * 退货发货
     */
    case ReturnShipped = 'return_shipped';

    /**
     * 退货签收
     */
    case ReturnReceived = 'return_received';

    /**
     * 开始处理
     */
    case Processing = 'processing';

    /**
     * 退款完成
     */
    case Completed = 'completed';

    /**
     * 退款失败
     */
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Created => '创建退款',
            self::Approved => '审核通过',
            self::Rejected => '审核拒绝',
            self::Cancelled => '取消退款',
            self::WaitingReturn => '等待退货',
            self::ReturnShipped => '退货发货',
            self::ReturnReceived => '退货签收',
            self::Processing => '开始处理',
            self::Completed => '退款完成',
            self::Failed => '退款失败',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Created => 'gray',
            self::Approved => 'green',
            self::Rejected => 'red',
            self::Cancelled => 'orange',
            self::WaitingReturn => 'violet',
            self::ReturnShipped => 'cyan',
            self::ReturnReceived => 'teal',
            self::Processing => 'blue',
            self::Completed => 'emerald',
            self::Failed => 'red',
        };
    }
}
