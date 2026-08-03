<?php

namespace App\Filament\Tenant\Clusters\Campaign;

use App\Enums\System\AvailableModule;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class CampaignCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = '营销活动';

    protected static ?int $navigationSort = 40;

    /**
     * 是否可访问
     *
     * 校验租户是否已启用活动模块。
     */
    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return (bool) $tenant?->hasModule(AvailableModule::Campaign);
    }
}
