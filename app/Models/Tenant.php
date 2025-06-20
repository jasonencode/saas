<?php

namespace App\Models;

use App\Factories\Loggable;
use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\TenancyRelations;
use App\Services\TenantService;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Tenant extends Model implements HasName, HasAvatar, HasCurrentTenantLabel
{
    use HasEasyStatus,
        TenancyRelations,
        SoftDeletes;

    protected $casts = [
        'config' => 'json',
        'expired_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::created(function(Tenant $tenant) {
            resolve(TenantService::class)
                ->autoMakePermissions($tenant);

            Loggable::make()
                ->on($tenant)
                ->log('创建租户【:subject.name】');
        });
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (!$this->avatar) {
            return '/images/avatar.svg';
        }

        return Storage::url($this->avatar);

    }

    public function getCurrentTenantLabel(): string
    {
        return '当前应用';
    }
}
