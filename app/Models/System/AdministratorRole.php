<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Unguarded]
#[WithoutIncrementing]
class AdministratorRole extends Pivot
{
    /**
     * 关联管理员
     *
     * @return BelongsTo<Administrator>
     */
    public function administrator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class);
    }

    /**
     * 关联角色
     *
     * @return BelongsTo<AdminRole>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class);
    }
}
