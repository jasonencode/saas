<?php

namespace App\Models;

use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use App\Policies\TaskPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 任务模型
 */
#[Unguarded]
#[UsePolicy(TaskPolicy::class)]
class Task extends Model
{
    use HasEasyStatus,
        HasSortable;

    protected $casts = [
        'options' => 'json',
    ];

    /**
     * 关联计划
     *
     * @return BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
