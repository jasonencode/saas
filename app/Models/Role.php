<?php

namespace App\Models;

use App\Models\Traits\BelongsToTeam;
use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class Role extends Model
{
    use BelongsToTeam,
        Cachable,
        SoftDeletes;

    protected $casts = [
        'is_sys' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::deleting(function(Role $role) {
            if ($role->is_sys) {
                throw new InvalidArgumentException('系统角色无法删除');
            }
        });
    }

    public function staffers(): BelongsToMany
    {
        return $this->belongsToMany(
            Staffer::class,
            'staffer_role',
            'role_id'
        )
            ->withTimestamps();
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class, 'role_id');
    }
}