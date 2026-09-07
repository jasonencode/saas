<?php

namespace App\Models\Traits;

use App\Enums\Mall\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * 订单查询作用域特征
 *
 * @property OrderStatus $status
 */
trait OrderScopes
{
    /**
     * 待付款
     */
    #[Scope]
    protected function ofPending(Builder $query): void
    {
        $query->where('status', OrderStatus::Pending);
    }

    /**
     * 待发货（已支付、备货中、部分发货）
     */
    #[Scope]
    protected function ofReadyToShip(Builder $query): void
    {
        $query->whereIn('status', [
            OrderStatus::Paid,
            OrderStatus::Preparing,
            OrderStatus::PartiallyShipped,
        ]);
    }

    /**
     * 待收货（已发货、已签收、待自提）
     */
    #[Scope]
    protected function ofAwaitingReceipt(Builder $query): void
    {
        $query->whereIn('status', [
            OrderStatus::Delivered,
            OrderStatus::Signed,
            OrderStatus::PickupPending,
        ]);
    }

    /**
     * 已完成（已核销、已完成，覆盖所有履约类型的终态）
     */
    #[Scope]
    protected function ofFinished(Builder $query): void
    {
        $query->whereIn('status', [
            OrderStatus::Verified,
            OrderStatus::Completed,
        ]);
    }
}
