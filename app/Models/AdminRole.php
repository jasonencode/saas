<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Filament\Notifications\Notification;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminRole extends Model
{
    use BelongsToTenant,
        Cachable,
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

    public function permissions(): HasMany
    {
        return $this->hasMany(AdminRolePermission::class, 'role_id');
    }
}
