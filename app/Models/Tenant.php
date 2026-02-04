<?php

namespace App\Models;

use App\Models\Traits\HasEasyStatus;
use App\Services\TenantService;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model implements HasName, HasAvatar, HasCurrentTenantLabel
{
    use HasEasyStatus,
        SoftDeletes;

    protected $casts = [
        'config' => 'json',
        'expired_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function (Tenant $tenant) {
            resolve(TenantService::class)
                ->autoMakePermissions($tenant);
        });
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (!$this->avatar) {
            return '/images/avatar.jpg';
        }

        return Storage::url($this->avatar);
    }

    public function getCurrentTenantLabel(): string
    {
        return '当前应用';
    }

    public function administrators(): BelongsToMany
    {
        return $this->belongsToMany(Administrator::class, 'administrator_tenant')
            ->using(AdministratorTenant::class)
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
