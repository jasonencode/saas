<?php

namespace App\Models\System;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Unguarded]
#[WithoutIncrementing]
class AdministratorTenant extends Pivot
{
    use BelongsToTenant;

    /**
     * 管理员关联
     *
     * @return BelongsTo
     */
    public function administrator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class);
    }
}
