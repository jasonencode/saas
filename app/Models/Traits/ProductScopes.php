<?php

namespace App\Models\Traits;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * 商品查询作用域特征
 *
 * @module 商城
 */
trait ProductScopes
{
    /**
     * 状态作用域
     *
     * @param  Builder  $query
     * @param  ProductStatus  $status
     * @return void
     */
    #[Scope]
    public function ofStatus(Builder $query, ProductStatus $status): void
    {
        $query->where('status', $status);
    }

    /**
     * 待审核作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    public function ofPending(Builder $query): void
    {
        $query->where('status', ProductStatus::Pending);
    }

    /**
     * 审核通过作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    public function ofPass(Builder $query): void
    {
        $query->where('status', ProductStatus::Approved);
    }

    /**
     * 上架作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    public function ofUp(Builder $query): void
    {
        $query->where('status', ProductStatus::Up);
    }

    /**
     * 拒绝作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    public function ofReject(Builder $query): void
    {
        $query->where('status', ProductStatus::Rejected);
    }

    /**
     * 下架作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    public function ofDown(Builder $query): void
    {
        $query->where('status', ProductStatus::Down);
    }
}
