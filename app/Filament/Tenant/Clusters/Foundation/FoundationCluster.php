<?php

namespace App\Filament\Tenant\Clusters\Foundation;

use App\Enums\System\AvailableModule;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class FoundationCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = '基础设施';

    protected static ?int $navigationSort = 80;

    /**
     * 是否可访问
     *
     * 校验租户是否已启用基础设施模块。
     */
    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return (bool) $tenant?->hasModule(AvailableModule::Foundation);
    }
}
