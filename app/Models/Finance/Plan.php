<?php

namespace App\Models\Finance;

use App\Models\Model;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use App\Policies\PlanPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 计划模型
 */
#[Unguarded]
#[UsePolicy(PlanPolicy::class)]
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
