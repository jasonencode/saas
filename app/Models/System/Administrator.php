<?php

namespace App\Models\System;

use App\Contracts\Authenticatable;
use App\Enums\System\AdminType;
use App\Policies\System\AdministratorPolicy;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use RuntimeException;

#[Hidden(['password'])]
#[Unguarded]
#[UsePolicy(AdministratorPolicy::class)]
class Administrator extends Authenticatable implements FilamentUser, HasAvatar, HasName, HasTenants
{
    use HasApiTokens,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => AdminType::class,
            'password' => 'hashed',
        ];
    }

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
     */
    public function isAdministrator(): bool
    {
        return $this->getKey() === 1 || $this->adminRoles()->where('is_sys', true)->exists();
    }

    /**
     * 管理员角色关联
     */
    public function adminRoles(): BelongsToMany
    {
        return $this->roles();
    }

    /**
     * 角色关联
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
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'administrator_tenant')
            ->using(AdministratorTenant::class)
            ->withTimestamps();
    }

    /**
     * 获取Filament用户头像URL
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
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * 租户关联
     */
    public function tenant(): BelongsToMany
    {
        return $this->tenants();
    }

    /**
     * 租户访问权限
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenants()->whereKey($tenant)->exists();
    }

    /**
     * 获取Filament用户租户列表
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->tenants;
    }
}
