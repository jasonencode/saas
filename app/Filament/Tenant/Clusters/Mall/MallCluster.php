<?php

namespace App\Filament\Tenant\Clusters\Mall;

use App\Enums\System\AvailableModule;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class MallCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = '商城模块';

    protected static ?int $navigationSort = 3;

    /**
     * 是否可访问
     *
     * 同时校验：租户已启用商城模块、且商城已开通（storeConfigure.enabled）。
     */
    public static function canAccess(): bool
    {
        return static::isModuleEnabled() && static::isStoreOpened();
    }

    /**
     * 租户是否已启用商城模块
     *
     * @return bool 是否启用
     */
    public static function isModuleEnabled(): bool
    {
        $tenant = Filament::getTenant();

        return (bool) $tenant?->hasModule(AvailableModule::Mall);
    }

    /**
     * 商城是否已开通（当前租户）
     *
     * @return bool 是否开通
     */
    public static function isStoreOpened(): bool
    {
        $tenant = Filament::getTenant();

        return (bool) $tenant?->storeConfigure?->isOpened();
    }
}
