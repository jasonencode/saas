<?php

namespace App\Models;

use App\Contracts\Authenticatable;
use App\Enums\AdminType;
use App\Factories\Loggable;
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
use Laravel\Sanctum\HasApiTokens;

class Administrator extends Authenticatable implements FilamentUser, HasAvatar, HasName, HasTenants
{
    use HasApiTokens,
        SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'type' => AdminType::class,
        'password' => 'hashed',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function(Administrator $administrator) {
            Loggable::make()
                ->on($administrator)
                ->log('创建管理员【:subject.username】');
        });

        self::deleted(static function(Administrator $administrator) {
            Loggable::make()
                ->on($administrator)
                ->log('删除管理员【:subject.username】');
        });
    }

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

    /**
     * 超级管理员标识
     *
     * @return bool
     */
    public function isAdministrator(): bool
    {
        return $this->getKey() === 1 || $this->adminRoles()->where('is_sys', true)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'tenant') {
            return $this->tenants()->count();
        }

        return !$this->tenants()->count();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (!$this->avatar) {
            return '/images/avatar.svg';
        }

        return Storage::url($this->avatar);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function tenant(): BelongsToMany
    {
        return $this->tenants();
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'administrator_tenant')
            ->withTimestamps();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenants()->whereKey($tenant)->exists();
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->tenants;
    }

    protected function getNameAttribute(): ?string
    {
        return $this->attributes['name'];
    }
}
