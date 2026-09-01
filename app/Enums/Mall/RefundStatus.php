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
            self::Pending => 'warning',
            self::WaitingReturn => 'orange',
            self::Shipping => 'info',
            self::Received => 'purple',
            self::Processing => 'primary',
            self::Completed => 'success',
            self::Rejected => 'danger',
            self::Cancelled => 'gray',
            self::Failed => 'danger',
        };
    }

    /**
     * 解析列表筛选状态
     *
     * 前端「退款中」（processing）为组合状态，覆盖退货中/已签收/退款处理中；
     * 其余状态单值匹配，未知值返回空数组（不匹配任何记录）。
     *
     * @param  string  $value  前端传入的状态值
     *
     * @return static[] 匹配的状态列表
     */
    public static function resolveFilterStatuses(string $value): array
    {
        return match ($value) {
            self::Processing->value => [self::Shipping, self::Received, self::Processing],
            default => self::tryFrom($value) ? [self::from($value)] : [],
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
