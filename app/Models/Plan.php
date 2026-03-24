<?php

namespace App\Models;

use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 计划模型
 */
#[Unguarded]
class Plan extends Model
{
    use HasEasyStatus,
        HasSortable,
        SoftDeletes;

    /**
     * 关联任务
     *
     * @return HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
