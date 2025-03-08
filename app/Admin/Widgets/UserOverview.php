<?php

namespace App\Admin\Widgets;

use App\Admin\Clusters\Contents\Resources\ExamineResource;
use App\Admin\Clusters\Users\Resources\UserResource;
use App\Enums\ExamineState;
use App\Models\Examine;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Mall\Enums\OrderStatus;
use Modules\Mall\Filament\Resources\OrderResource;
use Modules\Mall\Models\Order;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Filament\Resources\PaymentResource;
use Modules\Payment\Models\Payment;
use Nwidart\Modules\Facades\Module;

class UserOverview extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $states = [
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

        if (Module::has('Mall') && Module::isEnabled('Mall')) {
            $states[] = Stat::make('待发货订单', Order::where('status', OrderStatus::Paid)->count())
                ->description('今日订单：'.Order::whereDate('created_at', Carbon::today())->count())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->url(OrderResource::getUrl());
        }

        if (Module::has('Payment') && Module::isEnabled('Payment')) {
            $states[] = Stat::make('今日收款', Payment::where('status', PaymentStatus::Paid)->sum('amount'))
                ->description('收款订单：'.Payment::whereDate('created_at', Carbon::today())->count())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->url(PaymentResource::getUrl());
        }

        return $states;
    }
}