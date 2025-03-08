<?php

namespace App\Models\Traits;

use App\Models\Role;
use App\Models\Staffer;
use App\Models\StafferTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait TenancyRelations
{
    public function staffers(): BelongsToMany
    {
        return $this->belongsToMany(Staffer::class)
            ->using(StafferTeam::class)
            ->withTimestamps();
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}