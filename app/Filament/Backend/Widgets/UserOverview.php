<?php

namespace App\Filament\Backend\Widgets;

use App\Filament\Backend\Clusters\User\Resources\Users\UserResource;
use App\Models\User\User;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;

class UserOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            StatsOverviewWidget\Stat::make('用户数量', User::count())
                ->description('今日新增：'.User::whereDate('created_at', Carbon::today())->count())
                ->descriptionIcon(Heroicon::OutlinedArrowTrendingUp)
                ->color('success')
                ->url(UserResource::getUrl()),
        ];
    }
}
