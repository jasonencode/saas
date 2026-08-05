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
     * 按排序倒序（数字越大越靠前）
     */
    #[Scope]
    protected function bySort(Builder $query): void
    {
        $query->orderByDesc('sort')->latest();
    }

    /**
     * 按排序正序（数字越小越靠前）
     */
    #[Scope]
    protected function bySortAsc(Builder $query): void
    {
        $query->orderBy('sort')->oldest();
    }

    /**
     * 批量重排（传入 ID 列表，按顺序更新 sort 值）
     *
     * @param  iterable<int>  $ids  按新顺序排列的 ID 列表
     *
     * @return int 更新行数
     */
    public static function reorder(iterable $ids): int
    {
        $count = 0;

        foreach ($ids as $index => $id) {
            $updated = static::where('id', $id)->update(['sort' => $index]);
            $count += $updated;
        }

        return $count;
    }
}
