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
     */
    #[Scope]
    protected function ofPending(Builder $query): void
    {
        $query->where('status', ProductStatus::Pending);
    }

    /**
     * 上架作用域
     */
    #[Scope]
    protected function ofUp(Builder $query): void
    {
        $query->where('status', ProductStatus::Up);
    }

    /**
     * 拒绝作用域
     */
    #[Scope]
    protected function ofReject(Builder $query): void
    {
        $query->where('status', ProductStatus::Rejected);
    }

    /**
     * 下架作用域
     */
    #[Scope]
    protected function ofDown(Builder $query): void
    {
        $query->where('status', ProductStatus::Down);
    }

    /**
     * 排序作用域
     *
     * 支持的排序方式：
     * - price_asc / price_desc: 按价格排序
     * - sales_asc / sales_desc: 按销量排序
     * - newest: 最新上架
     */
    #[Scope]
    protected function orderByMatch(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderByRaw('(SELECT MIN(price) FROM skus WHERE skus.product_id = products.id) ASC'),
            'price_desc' => $query->orderByRaw('(SELECT MIN(price) FROM skus WHERE skus.product_id = products.id) DESC'),
            'sales_asc' => $query->orderByRaw('(SELECT COALESCE(SUM(sale), 0) FROM skus WHERE skus.product_id = products.id) ASC'),
            'sales_desc' => $query->orderByRaw('(SELECT COALESCE(SUM(sale), 0) FROM skus WHERE skus.product_id = products.id) DESC'),
            'newest' => $query->latest(),
            default => $query->latest(),
        };
    }
}
