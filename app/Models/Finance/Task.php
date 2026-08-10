<?php

namespace App\Models\Finance;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\HasSortable;
use App\Policies\Finance\TaskPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
#[UsePolicy(TaskPolicy::class)]
class Task extends Model
{
    use BelongsToTenant,
        HasEasyStatus,
        HasSortable;

    protected function casts(): array
    {
        return [
            'options' => 'json',
        ];
    }

    /**
     * 关联计划
     *
     * @return BelongsTo<Plan>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
