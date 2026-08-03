<?php

namespace App\Models\System;

use App\Models\Model;
use App\Models\Traits\BelongsToTenant;
use App\Policies\System\AdminRolePolicy;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Unguarded]
#[UsePolicy(AdminRolePolicy::class)]
class AdminRole extends Model
{
    use BelongsToTenant,
        SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_sys' => 'boolean',
        ];
    }

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
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(AdminRolePermission::class, 'role_id');
    }
}
