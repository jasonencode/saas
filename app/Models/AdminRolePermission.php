<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 后台管理员角色权限关联模型
 *
 * @module 后台
 */
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
