<?php

namespace App\Filament\Backend\Widgets;

use App\Enums\ExamineState;
use App\Models\Examine;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;

class UserOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            StatsOverviewWidget\Stat::make('用户数量', User::count())
                ->description('今日新增：'.User::whereDate('created_at', Carbon::today())->count())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            StatsOverviewWidget\Stat::make('内容审核', Examine::where('state', ExamineState::Pending)->count())
                ->description('今日新增：'.Examine::whereDate('created_at', Carbon::today())->count())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}
