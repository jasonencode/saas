<?php

namespace App\Models\Traits;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * 订单查询作用域特征
 *
 * @module 商城
 */
trait OrderScopes
{
    /**
     * 状态作用域
     *
     * @param  Builder  $query
     * @param  OrderStatus  $state
     * @return void
     */
    #[Scope]
    public function ofStatus(Builder $query, OrderStatus $state): void
    {
        $query->where('status', $state);
    }

    /**
     * 待支付作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    public function ofPending(Builder $query): void
    {
        $query->where('status', OrderStatus::Pending);
    }

    /**
     * 已取消作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    public function ofCanceled(Builder $query): void
    {
        $query->where('status', OrderStatus::Canceled);
    }

    /**
     * 已支付作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    public function ofPaid(Builder $query): void
    {
        $query->where('status', OrderStatus::Paid);
    }

    /**
     * 已发货作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    public function ofDelivered(Builder $query): void
    {
        $query->where('status', OrderStatus::Delivered);
    }

    /**
     * 已签收作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    public function ofSigned(Builder $query): void
    {
        $query->where('status', OrderStatus::Signed);
    }

    /**
     * 已完成作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    public function ofCompleted(Builder $query): void
    {
        $query->where('status', OrderStatus::Completed);
    }
}
