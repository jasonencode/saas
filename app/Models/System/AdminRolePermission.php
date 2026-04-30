<?php

namespace App\Models\System;

use App\Models\Model;
use App\Policies\System\AdminRolePermissionPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Unguarded]
#[UsePolicy(AdminRolePermissionPolicy::class)]
class AdminRolePermission extends Model
{
    const null UPDATED_AT = null;

    /**
     * 角色关联
     *
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(AdminRole::class);
    }
}
