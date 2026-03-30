<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Policies\AdminRolePolicy;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 后台管理员角色模型
 */
#[Unguarded]
#[UsePolicy(AdminRolePolicy::class)]
class AdminRole extends Model
{
    use BelongsToTenant,
        SoftDeletes;

    protected $casts = [
        'is_sys' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(static function (AdminRole $role) {
            if ($role->is_sys) {
                Notification::make()
                    ->title('系统级角色不能删除')
                    ->danger()
                    ->send();

                return false;
            }

            return true;
        });
    }

    /**
     * 管理员关联
     *
     * @return BelongsToMany
     */
    public function administrators(): BelongsToMany
    {
        return $this->belongsToMany(
            Administrator::class,
            'administrator_role',
            'role_id'
        )
            ->using(AdministratorRole::class)
            ->withTimestamps();
    }

    /**
     * 角色权限关联
     *
     * @return HasMany
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(AdminRolePermission::class, 'role_id');
    }
}
