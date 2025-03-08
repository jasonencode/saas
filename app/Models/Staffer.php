<?php

namespace App\Models;

use App\Contracts\Authenticatable;
use App\Models\Traits\HasEasyStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Staffer extends Authenticatable implements FilamentUser, HasTenants, HasAvatar
{
    use HasEasyStatus,
        SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function isAdministrator(): bool
    {
        return true;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status;
    }

    public function team(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->using(StafferTeam::class)
            ->withTimestamps();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->teams()->whereKey($tenant)->exists();
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)
            ->using(StafferTeam::class)
            ->withTimestamps();
    }

    public function getTenants(Panel $panel): array|Collection
    {
        return $this->teams;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return Storage::url($this->avatar);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'staffer_role')
            ->withTimestamps();
    }

    protected function getNameAttribute(): ?string
    {
        return $this->attributes['name'];
    }
}
