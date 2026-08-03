<?php

namespace App\Filament\Tenant\Clusters\Mall;

use App\Models\Mall\StoreConfigure;
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
     * 商城是否已开通（当前租户）
     *
     * @return bool 是否开通
     */
    public static function isStoreOpened(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant !== null
            && StoreConfigure::isTenantOpened((int) $tenant->getKey());
    }

    /**
     * 是否注册到导航
     *
     * 商城未开通时不注册导航，整个 cluster 在侧边栏不显示。
     */
    public static function shouldRegisterNavigation(): bool
    {
        return parent::shouldRegisterNavigation()
            && static::isStoreOpened();
    }

    /**
     * 是否可访问
     *
     * 商城未开通时禁止直接通过 URL 访问 cluster 下的所有页面。
     */
    public static function canAccess(): bool
    {
        return parent::canAccess()
            && static::isStoreOpened();
    }
}
