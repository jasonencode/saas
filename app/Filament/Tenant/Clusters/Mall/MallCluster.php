<?php

namespace App\Filament\Tenant\Clusters\Mall;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class MallCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = '商城模块';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return static::isStoreOpened();
    }

    /**
     * 商城是否已开通（当前租户）
     *
     * 供本 cluster 下各页面复用，用于商城关闭时隐藏导航项与拦截 URL 直达。
     *
     * @return bool 是否开通
     */
    public static function isStoreOpened(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant?->storeConfigure?->isOpened() ?? false;
    }
}
