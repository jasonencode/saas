<?php

namespace App\Models\System;

use App\Models\Model;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
class AdminRolePermission extends Model
{
    const null UPDATED_AT = null;

    /**
     * 角色关联
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class);
    }
}
