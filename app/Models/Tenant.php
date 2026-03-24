<?php

namespace App\Models;

use App\Models\Traits\HasEasyStatus;
use App\Services\TenantService;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * 租户模型
 */
#[Unguarded]
class Tenant extends Model implements HasName, HasAvatar, HasCurrentTenantLabel
{
    use HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'config' => 'json',
        'expired_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function (Tenant $tenant) {
            resolve(TenantService::class)
                ->autoMakePermissions($tenant);
        });
    }

    /**
     * 获取Filament显示名称
     *
     * @return string
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * 获取Filament头像
     *
     * @return string|null
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if (!$this->avatar) {
            return '/images/avatar.jpg';
        }

        return Storage::url($this->avatar);
    }

    /**
     * 获取当前租户标签
     *
     * @return string
     */
    public function getCurrentTenantLabel(): string
    {
        return '当前应用';
    }

    /**
     * 关联管理员
     *
     * @return BelongsToMany
     */
    public function administrators(): BelongsToMany
    {
        return $this->belongsToMany(Administrator::class, 'administrator_tenant')
            ->using(AdministratorTenant::class)
            ->withTimestamps();
    }

    /**
     * 关联角色
     *
     * @return HasMany
     */
    public function roles(): HasMany
    {
        return $this->hasMany(AdminRole::class);
    }

    /**
     * 关联用户
     *
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
