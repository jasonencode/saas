<?php

namespace App\Models;

use App\Factories\Loggable;
use App\Models\Traits\BelongsToTenant;
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

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function(AdminRole $role) {
            Loggable::make()
                ->on($role)
                ->log('创建角色【:subject.name】');
        });

        self::deleted(static function(AdminRole $role) {
            Loggable::make()
                ->on($role)
                ->log('删除角色【:subject.name】');
        });
    }

    public function administrators(): BelongsToMany
    {
        return $this->belongsToMany(
            Administrator::class,
            'administrator_role',
            'role_id'
        )
            ->withTimestamps();
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(AdminRolePermission::class, 'role_id');
    }
}
