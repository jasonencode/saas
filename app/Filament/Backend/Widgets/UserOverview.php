<?php

namespace App\Filament\Backend\Widgets;

use App\Filament\Backend\Clusters\User\Resources\Users\UserResource;
use App\Models\User\User;
use App\Models\User\UserRealname;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;

class UserOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $total = User::count();

        return [
            StatsOverviewWidget\Stat::make('用户总数', $total)
                ->description('今日新增：'.User::whereDate('created_at', Carbon::today())->count())
                ->descriptionIcon(Heroicon::OutlinedUsers)
                ->color('success')
                ->url(UserResource::getUrl()),

            StatsOverviewWidget\Stat::make('已实名认证', UserRealname::where('status', 'approved')->count())
                ->description('实名率：'.($total > 0 ? round(UserRealname::where('status', 'approved')->count() / $total * 100, 1).'%' : '0%'))
                ->descriptionIcon(Heroicon::OutlinedIdentification)
                ->color('info'),

            StatsOverviewWidget\Stat::make('待审实名', UserRealname::where('status', 'pending')->count())
                ->description('等待审核的实名认证申请')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning'),
        ];
    }
}
