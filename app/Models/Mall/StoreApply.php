<?php

namespace App\Models\Mall;

use App\Enums\Mall\ApplyStatus;
use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Models\Traits\HasCovers;
use App\Policies\Mall\StoreApplyPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(StoreApplyPolicy::class)]
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
