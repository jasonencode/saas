<?php

namespace App\Models\System;

use App\Contracts\Authenticatable;
use App\Enums\System\AvailableModule;
use App\Models\Finance\UserAccount;
use App\Models\Mall\StoreConfigure;
use App\Models\Traits\HasEasyStatus;
use App\Models\User\User;
use App\Policies\System\TenantPolicy;
use App\Services\User\TenantService;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[Unguarded]
#[UsePolicy(TenantPolicy::class)]
class Tenant extends Authenticatable implements HasAvatar, HasCurrentTenantLabel, HasName
{
    use HasApiTokens,
        HasEasyStatus,
        Notifiable,
        SoftDeletes;

    /**
     * 已启用模块列表的运行内缓存
     *
     * 仅挂在本实例上，不参与 Eloquent 的 attributes/序列化，请求结束即释放；
     * 不使用静态属性，避免 Octane 跨请求复用单例时累积/串租户。
     */
    private ?array $modulesCache = null;

    protected function casts(): array
    {
        return [
            'config' => 'json',
            'expired_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function (Tenant $tenant) {
            service(TenantService::class)
                ->autoMakePermissions($tenant);
        });
    }

    /**
     * 获取Filament显示名称
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }

    /**
     * 获取Filament头像
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
     */
    public function getCurrentTenantLabel(): string
    {
        return '当前应用';
    }

    /**
     * 关联管理员
     *
     * @return BelongsToMany<Administrator>
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
     * @return HasMany<AdminRole>
     */
    public function roles(): HasMany
    {
        return $this->hasMany(AdminRole::class);
    }

    /**
     * 关联用户
     *
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * 关联用户账户
     *
     * @return HasManyThrough<UserAccount>
     */
    public function accounts(): HasManyThrough
    {
        return $this->hasManyThrough(UserAccount::class, User::class);
    }

    /**
     * 租户商城配置
     *
     * @return HasOne<StoreConfigure>
     */
    public function storeConfigure(): HasOne
    {
        return $this->hasOne(StoreConfigure::class);
    }

    /**
     * 获取租户已启用的模块列表
     *
     * 存于 config.modules 字段（数组），未配置时返回 null 表示「未限定，全部可用」。
     * 结果缓存到本实例的 $modulesCache 属性上，同一请求内多次调用只解析一次；
     * 不使用静态属性，避免 Octane 跨请求复用单例时累积/串租户。
     *
     * @return array<AvailableModule>|null 已启用模块列表；null 表示未限定（全部可用）
     */
    public function getModules(): ?array
    {
        if ($this->modulesCache !== null) {
            return $this->modulesCache;
        }

        $modules = $this->config['modules'] ?? null;

        // 未配置 modules 键 → 未限定，全部可用（兼容现存租户）
        if ($modules === null) {
            return null;
        }

        return $this->modulesCache = array_filter(
            array_map(static fn (string $value) => AvailableModule::tryFrom($value), $modules),
            static fn ($module) => $module !== null,
        );
    }

    /**
     * 判断租户是否已启用指定模块
     *
     * 语义：config.modules 未配置时视为全部可用（默认开启）；
     * 显式配置了 modules 清单时，仅清单内模块可用。
     *
     * @param  AvailableModule  $module  模块枚举
     *
     * @return bool 是否已启用
     */
    public function hasModule(AvailableModule $module): bool
    {
        $modules = $this->getModules();

        // 未限定 → 全部可用
        return $modules === null || in_array($module, $modules, true);
    }
}
