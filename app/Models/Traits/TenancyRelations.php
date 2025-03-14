<?php

namespace App\Models\Traits;

use App\Models\Administrator;
use App\Models\AdminRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait TenancyRelations
{
    public function administrators(): BelongsToMany
    {
        return $this->belongsToMany(Administrator::class, 'administrator_tenant')
            ->withTimestamps();
    }

    public function roles(): HasMany
    {
        return $this->hasMany(AdminRole::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
