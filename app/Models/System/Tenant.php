<?php

namespace App\Models\System;

use App\Contracts\Authenticatable;
use App\Enums\System\AvailableModule;
use App\Models\Mall\StoreConfigure;
use App\Models\Traits\HasEasyStatus;
use App\Models\User\User;
use App\Models\User\UserTenant;
use App\Policies\System\TenantPolicy;
use App\Services\User\TenantService;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     * @return BelongsToMany<User>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class.'user_tenant')
            ->using(UserTenant::class)
            ->withTimestamps();
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
     * 判断租户是否已过期
     *
     * expired_at 未设置时视为永不过期；已设置且早于当前时间则视为过期。
     */
    public function isExpired(): bool
    {
        return $this->expired_at !== null && $this->expired_at->isPast();
    }

    /**
     * 判断租户是否已启用指定模块
     *
     * 语义：显式配置了 modules 清单时，仅清单内模块可用；未配置时所有模块不可用。
     *
     * @param  AvailableModule  $module  模块枚举
     *
     * @return bool 是否已启用
     */
    public function hasModule(AvailableModule $module): bool
    {
        $modules = $this->getModules();

        return in_array($module, $modules, true);
    }

    /**
     * 获取租户已启用的模块列表
     *
     * 存于 config.modules 字段（数组），未配置或为空时返回空数组表示「无可用模块」。
     * 结果缓存到本实例的 $modulesCache 属性上，同一请求内多次调用只解析一次；
     * 不使用静态属性，避免 Octane 跨请求复用单例时累积/串租户。
     *
     * @return array<AvailableModule> 已启用模块列表
     */
    public function getModules(): array
    {
        if ($this->modulesCache !== null) {
            return $this->modulesCache;
        }

        $modules = $this->config['modules'] ?? [];

        return $this->modulesCache = array_filter(
            array_map(static fn (string $value) => AvailableModule::tryFrom($value), $modules),
            static fn ($module) => $module !== null,
        );
    }
}
