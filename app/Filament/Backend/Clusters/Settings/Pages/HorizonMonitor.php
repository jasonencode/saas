<?php

namespace App\Filament\Backend\Clusters\Settings\Pages;

use App\Filament\Backend\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;
use function Filament\Support\original_request;

class HorizonMonitor extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::PresentationChartBar;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $modelLabel = '队列监控';

    protected static ?string $navigationLabel = '队列监控';

    protected static string|null|UnitEnum $navigationGroup = '维护';

    protected static ?int $navigationSort = 102;

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->parentItem(static::getNavigationParentItem())
                ->icon(static::getNavigationIcon())
                ->activeIcon(static::getActiveNavigationIcon())
                ->isActiveWhen(fn(): bool => original_request()->routeIs(static::getNavigationItemActiveRoutePattern()))
                ->sort(static::getNavigationSort())
                ->badge(static::getNavigationBadge(), color: static::getNavigationBadgeColor())
                ->badgeTooltip(static::getNavigationBadgeTooltip())
                ->url(static::getNavigationUrl(), true),
        ];
    }

    public static function getNavigationUrl(): string
    {
        return '/backend/horizon';
    }
}
