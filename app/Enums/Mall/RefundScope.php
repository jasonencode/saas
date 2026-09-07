<?php

namespace App\Enums\Mall;

use App\Models\Mall\Refund;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;

/**
 * 退款单筛选作用域枚举
 *
 * 统一驱动后端/租户管理面板与 API 的退款单分状态筛选。
 */
enum RefundScope: string implements HasColor, HasLabel
{
    /**
     * 待审核
     */
    case Pending = 'pending';

    /**
     * 处理中
     */
    case Processing = 'processing';

    /**
     * 已完成
     */
    case Completed = 'completed';

    /**
     * 已关闭
     */
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待审核',
            self::Processing => '处理中',
            self::Completed => '已完成',
            self::Closed => '已关闭',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processing => 'primary',
            self::Completed => 'success',
            self::Closed => 'gray',
        };
    }

    /**
     * 将对应的 scope 应用到查询
     */
    public function apply(Builder|Refund $query): void
    {
        match ($this) {
            self::Pending => $query->ofPending(),
            self::Processing => $query->ofProcessing(),
            self::Completed => $query->ofCompleted(),
            self::Closed => $query->ofClosed(),
        };
    }
}
