<?php

namespace App\Models;

use App\Contracts\Authenticatable;
use App\Enums\AdminType;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Administrator extends Authenticatable implements FilamentUser, HasAvatar, HasName, HasTenants
{
    use SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'type' => AdminType::class,
        'password' => 'hashed',
    ];

    public function adminRoles(): BelongsToMany
    {
        return $this->roles();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            AdminRole::class,
            'administrator_role',
            'administrator_id',
            'role_id',
        )
            ->withTimestamps();
    }

    public function isAdministrator(): bool
    {
        return true;
        return $this->getKey() == 1;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (!$this->avatar) {
            return '/images/avatar.jpg';
        }

        return Storage::url($this->avatar);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    protected function getNameAttribute(): ?string
    {
        return $this->attributes['name'];
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'administrator_tenant')
            ->withTimestamps();
    }

    public function tenant(): BelongsToMany
    {
        return $this->tenants();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenants()->whereKey($tenant)->exists();
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->tenants;
    }
}
