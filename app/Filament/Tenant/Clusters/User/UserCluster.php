<?php

namespace App\Filament\Tenant\Clusters\User;

use App\Enums\System\AvailableModule;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class UserCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 1;

    /**
     * 是否可访问
     *
     * 校验租户是否已启用用户模块。
     */
    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return (bool) $tenant?->hasModule(AvailableModule::User);
    }
}
