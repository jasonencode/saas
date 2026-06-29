<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * 排序特征
 *
 * @property int $sort
 */
trait HasSortable
{
    /**
     * 排序作用域
     */
    #[Scope]
    protected function bySort(Builder $query): void
    {
        $query->orderByDesc('sort')->latest();
    }
}
