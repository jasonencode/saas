<?php

namespace App\Models\Traits;

use App\Enums\Mall\ProductStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * 商品查询作用域特征
 *
 * @property ProductStatus $status
 *
 * @method Builder ofPending()
 * @method Builder ofUp()
 * @method Builder ofReject()
 * @method Builder ofDown()
 */
trait ProductScopes
{
    /**
     * 待审核作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    protected function ofPending(Builder $query): void
    {
        $query->where('status', ProductStatus::Pending);
    }

    /**
     * 上架作用域
     *
     * @param  Builder  $query
     * @return void
     */
    #[Scope]
    protected function ofUp(Builder $query): void
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
    protected function ofReject(Builder $query): void
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
    protected function ofDown(Builder $query): void
    {
        $query->where('status', ProductStatus::Down);
    }
}
