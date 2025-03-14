<?php

namespace App\Models;

use GeneaLabs\LaravelModelCaching\Traits\Cachable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminRole extends Model
{
    use Cachable,
        SoftDeletes;

    protected $casts = [
        'is_sys' => 'boolean',
    ];

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
