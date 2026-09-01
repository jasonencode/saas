<?php

namespace App\Models\Traits;

use App\Enums\Mall\ProductStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * 商品查询作用域特征
 *
 * @property ProductStatus $status
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
     * 筛选作用域
     *
     * 支持的筛选方式：
     * - hot: 热卖（有销量的商品，按总销量降序）
     * - new: 新品（最近 7 天上架）
     * - discount: 限时优惠（关联有效优惠券）
     * - freeShip: 包邮（无需物流配送）
     */
    #[Scope]
    protected function ofFilter(Builder $query, string $filter): void
    {
        match ($filter) {
            'hot' => $query->whereHas('skus', fn ($q) => $q->where('sale', '>', 0)),
            'new' => $query->where('created_at', '>=', now()->subDays(7)),
            'discount' => $query->whereHas('coupons', fn ($q) => $q->where('status', true)
                ->where(function ($q) {
                    $q->whereNull('start_at')->orWhere('start_at', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('end_at')->orWhere('end_at', '>=', now());
                })),
            'freeShip' => $query->where(fn ($q) => $q->whereNull('fulfillment_type')->orWhereRaw('NOT fulfillment_type @> \'["mail"]\'::jsonb')),
        };
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
            default => $query->latest(),
        };
    }
}
