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
     * 已缓存的权限标识集合（policy.method）
     *
     * @var array<int, string>|null
     */
    protected ?array $permissionKeys = null;

    /**
     * 是否拥有指定权限
     *
     * 权限集合仅首次调用查询一次数据库，后续检查在内存中完成。
     *
     * @param  string  $policy  权限策略标识
     * @param  string  $method  权限方法名
     *
     * @return bool 是否拥有权限
     */
    public function hasPermission(string $policy, string $method): bool
    {
        return in_array(
            sprintf('%s.%s', $policy, $method),
            $this->getPermissionKeys(),
            true
        );
    }

    /**
     * 获取当前用户全部权限标识（policy.method）
     *
     * @return array<int, string>
     */
    protected function getPermissionKeys(): array
    {
        return $this->permissionKeys ??= AdminRolePermission::query()
            ->whereIn('role_id', $this->roles->pluck('id')->all())
            ->get(['policy', 'method'])
            ->map(static fn (AdminRolePermission $permission): string => sprintf('%s.%s', $permission->policy, $permission->method))
            ->all();
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
