<?php

namespace App\Filament\Backend\Widgets;

use App\Enums\Mall\OrderStatus;
use App\Filament\Backend\Clusters\Mall\Resources\Orders\OrderResource;
use App\Models\Mall\Order;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;

class OrderOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        return [
            StatsOverviewWidget\Stat::make('订单总数', Order::count())
                ->description('今日订单：'.Order::whereDate('created_at', Carbon::today())->count())
                ->descriptionIcon(Heroicon::OutlinedShoppingCart)
                ->color('primary')
                ->url(OrderResource::getUrl()),

            StatsOverviewWidget\Stat::make('待发货', Order::where('status', OrderStatus::Paid)->count())
                ->description('已支付未发货的订单')
                ->descriptionIcon(Heroicon::OutlinedTruck)
                ->color('warning'),

            StatsOverviewWidget\Stat::make('今日销售额', '¥'.number_format(Order::whereDate('created_at', Carbon::today())
                ->whereIn('status', [OrderStatus::Paid, OrderStatus::Preparing, OrderStatus::PartiallyShipped, OrderStatus::Delivered, OrderStatus::Signed, OrderStatus::Completed])
                ->sum('amount'), 2))
                ->description('今日已支付订单总额')
                ->descriptionIcon(Heroicon::OutlinedCurrencyYen)
                ->color('success'),
        ];
    }
}
