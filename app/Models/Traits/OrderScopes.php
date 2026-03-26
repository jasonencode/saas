<?php

namespace App\Models\Traits;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * 订单查询作用域特征
 */
trait OrderScopes
{
    /**
     * 待付款作用域
     */
    #[Scope]
    protected function ofPending(Builder $query): void
    {
        $query->where('status', OrderStatus::Pending);
    }

    /**
     * 待发货作用域（已支付、备货中、部分发货的订单）
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
     * 已发货作用域（已发货、已签收的订单）
     */
    #[Scope]
    protected function ofDelivering(Builder $query): void
    {
        $query->whereIn('status', [
            OrderStatus::Delivered,
            OrderStatus::Signed,
        ]);
    }

    /**
     * 已签收作用域
     */
    #[Scope]
    protected function ofSigned(Builder $query): void
    {
        $query->where('status', OrderStatus::Signed);
    }

    /**
     * 已完成订单
     */
    #[Scope]
    protected function ofCompleted(Builder $query): void
    {
        $query->where('status', OrderStatus::Completed);
    }
}
