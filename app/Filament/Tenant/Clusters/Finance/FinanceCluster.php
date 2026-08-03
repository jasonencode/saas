<?php

namespace App\Filament\Tenant\Clusters\Finance;

use App\Enums\System\AvailableModule;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class FinanceCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCursorArrowRipple;

    protected static ?string $navigationLabel = '财务模块';

    protected static ?int $navigationSort = 90;

    /**
     * 是否可访问
     *
     * 校验租户是否已启用财务模块。
     */
    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return (bool) $tenant?->hasModule(AvailableModule::Finance);
    }
}
