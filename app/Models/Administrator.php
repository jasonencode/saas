<?php

namespace App\Models;

use App\Contracts\Authenticatable;
use App\Enums\AdminType;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use RuntimeException;

/**
 * 后台管理员模型
 *
 * @module 后台
 */
class Administrator extends Authenticatable implements FilamentUser, HasAvatar, HasName, HasTenants
{
    use HasApiTokens,
        SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'type' => AdminType::class,
        'password' => 'hashed',
    ];

    protected static function booted(): void
    {
        static::deleting(static function (Administrator $model): void {
            if ($model->isAdministrator()) {
                throw new RuntimeException('超级管理员禁止删除');
            }
        });
    }

    /**
     * 超级管理员标识
     *
     * @return bool
     */
    public function isAdministrator(): bool
    {
        return $this->getKey() === 1 || $this->adminRoles()->where('is_sys', true)->exists();
    }

    /**
     * 管理员角色关联
     *
     * @return BelongsToMany
     */
    public function adminRoles(): BelongsToMany
    {
        return $this->roles();
    }

    /**
     * 角色关联
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            AdminRole::class,
            'administrator_role',
            'administrator_id',
            'role_id',
        )
            ->using(AdministratorRole::class)
            ->withTimestamps();
    }

    /**
     * 面板访问权限
     *
     * @param  Panel  $panel
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'tenant') {
            return $this->tenants()->count();
        }

        return !$this->tenants()->count();
    }

    /**
     * 租户关联
     *
     * @return BelongsToMany
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'administrator_tenant')
            ->using(AdministratorTenant::class)
            ->withTimestamps();
    }

    /**
     * 获取Filament用户头像URL
     *
     * @return ?string
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if (!$this->avatar) {
            return '/images/avatar.jpg';
        }

        return Storage::url($this->avatar);
    }

    /**
     * 获取Filament用户名称
     *
     * @return string
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * 租户关联
     *
     * @return BelongsToMany
     */
    public function tenant(): BelongsToMany
    {
        return $this->tenants();
    }

    /**
     * 租户访问权限
     *
     * @param  Model  $tenant
     * @return bool
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenants()->whereKey($tenant)->exists();
    }

    /**
     * 获取Filament用户租户列表
     *
     * @param  Panel  $panel
     * @return Collection
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->tenants;
    }

    /**
     * 获取Filament用户名称
     *
     * @return string
     */
    protected function getNameAttribute(): ?string
    {
        return $this->attributes['name'];
    }
}
