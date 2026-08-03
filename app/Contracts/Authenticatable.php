<?php

namespace App\Contracts;

use App\Models\System\AdminRolePermission;
use App\Models\User\LoginRecord;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;

/**
 * 可认证用户基类
 */
abstract class Authenticatable extends User
{
    use HasFactory,
        Notifiable;

    /**
     * 是否拥有指定权限
     *
     * @param  string  $policy  权限策略标识
     * @param  string  $method  权限方法名
     *
     * @return bool 是否拥有权限
     */
    public function hasPermission(string $policy, string $method): bool
    {
        return AdminRolePermission::whereIn('role_id', $this->roles->pluck('id')->toArray())
            ->where('policy', $policy)
            ->where('method', $method)
            ->exists();
    }

    /**
     * 关联登录记录
     *
     * @return MorphMany<LoginRecord>
     */
    public function records(): MorphMany
    {
        return $this->morphMany(LoginRecord::class, 'user');
    }

    /**
     * 获取用户名
     */
    protected function getNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }
}
