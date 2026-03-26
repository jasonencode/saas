<?php

namespace App\Models;

use App\Enums\ApplyStatus;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 店铺申请模型
 */
#[Unguarded]
class StoreApply extends Model
{
    use BelongsToTenant,
        HasCovers,
        SoftDeletes;

    protected $casts = [
        'status' => ApplyStatus::class,
        'ext' => 'json',
    ];

    /**
     * 审核人
     */
    public function approver(): MorphTo
    {
        return $this->morphTo();
    }
}
