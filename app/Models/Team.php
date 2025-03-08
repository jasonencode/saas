<?php

namespace App\Models;

use App\Models\Traits\HasEasyStatus;
use App\Models\Traits\TenancyRelations;
use App\Services\TeamService;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Team extends Model implements HasName, HasAvatar, HasCurrentTenantLabel
{
    use HasEasyStatus,
        SoftDeletes,
        TenancyRelations;

    protected $casts = [
        'configs' => 'json',
    ];

    protected static function boot(): void
    {
        parent::boot();

        self::created(function (Team $team) {
            resolve(TeamService::class)->autoMakePermissions($team);
        });
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return Storage::url($this->avatar);
    }

    public function getCurrentTenantLabel(): string
    {
        return '当前应用';
    }
}
