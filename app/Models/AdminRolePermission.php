<?php

namespace App\Models;

use App\Policies\AdminRolePermissionPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 后台管理员角色权限关联模型
 */
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
