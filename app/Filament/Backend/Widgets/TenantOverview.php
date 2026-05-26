<?php

namespace App\Filament\Backend\Widgets;

use App\Filament\Backend\Clusters\User\Resources\Tenants\TenantResource;
use App\Models\System\Tenant;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;

class TenantOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            StatsOverviewWidget\Stat::make('租户总数', Tenant::count())
                ->description('今日新增：'.Tenant::whereDate('created_at', Carbon::today())->count())
                ->descriptionIcon(Heroicon::OutlinedBuildingStorefront)
                ->color('primary')
                ->url(TenantResource::getUrl()),

            StatsOverviewWidget\Stat::make('活跃租户', Tenant::where('status', true)->count())
                ->description('已启用 / '.Tenant::count().' 总租户')
                ->descriptionIcon(Heroicon::OutlinedCheckBadge)
                ->color('success'),

            StatsOverviewWidget\Stat::make('即将过期', Tenant::whereDate('expired_at', '<=', Carbon::today()->addDays(30))
                ->whereDate('expired_at', '>', Carbon::today())
                ->where('status', true)
                ->count())
                ->description('30天内到期的活跃租户')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning'),
        ];
    }
}
