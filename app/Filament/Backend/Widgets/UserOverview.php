<?php

namespace App\Filament\Backend\Widgets;

use App\Enums\ExamineState;
use App\Filament\Backend\Clusters\Contents\Resources\ExamineResource;
use App\Filament\Backend\Clusters\Users\Resources\UserResource;
use App\Models\Examine;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserOverview extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            Stat::make('用户数量', User::count())
                ->description('今日新增：'.User::whereDate('created_at', Carbon::today())->count())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->url(UserResource::getUrl()),
            Stat::make('内容审核', Examine::where('state', ExamineState::Pending)->count())
                ->description('今日新增：'.Examine::whereDate('created_at', Carbon::today())->count())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->url(ExamineResource::getUrl()),
        ];
    }
}
