<?php

namespace App\Enums\Mall;

use App\Models\Mall\Order;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Database\Eloquent\Builder;

/**
 * 订单筛选作用域枚举
 *
 * 统一驱动后端/租户管理面板与 API 的订单分状态筛选。
 */
enum OrderScope: string implements HasColor, HasLabel
{
    /**
     * 待付款
     */
    case Pending = 'pending';

    /**
     * 待发货
     */
    case ReadyToShip = 'ready_to_ship';

    /**
     * 待收货
     */
    case AwaitingReceipt = 'awaiting_receipt';

    /**
     * 已完成
     */
    case Finished = 'finished';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => '待付款',
            self::ReadyToShip => '待发货',
            self::AwaitingReceipt => '待收货',
            self::Finished => '已完成',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::ReadyToShip => 'blue',
            self::AwaitingReceipt => 'indigo',
            self::Finished => 'emerald',
        };
    }

    /**
     * 将对应的 scope 应用到查询
     */
    public function apply(Builder|Order $query): void
    {
        match ($this) {
            self::Pending => $query->ofPending(),
            self::ReadyToShip => $query->ofReadyToShip(),
            self::AwaitingReceipt => $query->ofAwaitingReceipt(),
            self::Finished => $query->ofFinished(),
        };
    }
}
