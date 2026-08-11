<?php

namespace App\Enums\Mall;

use App\Enums\Traits\HasStateMachine;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RefundStatus: string implements HasColor, HasLabel
{
    use HasStateMachine;

    case Pending = 'pending';

    case WaitingReturn = 'waiting_return';

    case Shipping = 'shipping';

    case Received = 'received';

    case Processing = 'processing';

    case Completed = 'completed';

    case Rejected = 'rejected';

    case Cancelled = 'cancelled';

    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待审核',
            self::WaitingReturn => '等待退货',
            self::Shipping => '退货中',
            self::Received => '已签收',
            self::Processing => '退款处理中',
            self::Completed => '退款完成',
            self::Rejected => '审核拒绝',
            self::Cancelled => '已取消',
            self::Failed => '退款失败',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::WaitingReturn => 'violet',
            self::Shipping => 'cyan',
            self::Received => 'teal',
            self::Processing => 'sky',
            self::Completed => 'emerald',
            self::Rejected => 'red',
            self::Cancelled => 'rose',
            self::Failed => 'orange',
        };
    }

    /**
     * 退款状态流转图：
     *
     * 仅退款：  Pending → Processing → Completed / Failed
     * 退货退款：Pending → WaitingReturn → Shipping → Received → Processing → Completed / Failed
     *
     * 通用：  Pending → Rejected / Canceled（终态）
     *         Failed ⇄ Processing（可重试）
     *
     * @return static[]
     */
    public function previous(?RefundType $type = null): array
    {
        return match ($this) {
            self::WaitingReturn, self::Rejected, self::Cancelled => [self::Pending],
            self::Shipping => [self::WaitingReturn],
            self::Received => [self::Shipping],
            self::Processing => match ($type) {
                RefundType::OnlyRefund => [self::Pending, self::Failed],
                RefundType::ReturnRefund => [self::Received, self::Failed],
                default => [self::Pending, self::Received, self::Failed],
            },
            self::Completed, self::Failed => [self::Processing],
            default => [],
        };
    }

    /**
     * @return static[]
     */
    public function next(?RefundType $type = null): array
    {
        return match ($this) {
            self::Pending => match ($type) {
                RefundType::OnlyRefund => [self::Processing, self::Rejected, self::Cancelled],
                RefundType::ReturnRefund => [self::WaitingReturn, self::Rejected, self::Cancelled],
                default => [self::WaitingReturn, self::Processing, self::Rejected, self::Cancelled],
            },
            self::WaitingReturn => [self::Shipping],
            self::Shipping => [self::Received],
            self::Received, self::Failed => [self::Processing],
            self::Processing => [self::Completed, self::Failed],
            default => [],
        };
    }
}
