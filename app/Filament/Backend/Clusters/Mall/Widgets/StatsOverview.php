<?php

namespace App\Filament\Backend\Clusters\Mall\Widgets;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('商品总数', Product::count())
                ->description('所有状态的商品')
                ->descriptionIcon('heroicon-o-archive-box')
                ->color('info'),

            Stat::make('上架商品', Product::where('status', ProductStatus::Up)->count())
                ->description('正在销售的商品')
                ->descriptionIcon('heroicon-o-shopping-bag')
                ->color('success'),

            Stat::make('订单总数', Order::count())
                ->description('所有订单')
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('warning'),

            Stat::make('待发货订单', Order::whereIn('status', [OrderStatus::Paid, OrderStatus::Preparing,])->count())
                ->description('需要发货的订单')
                ->descriptionIcon('heroicon-o-truck')
                ->color('danger'),

            Stat::make('退款单总数', Refund::count())
                ->description('所有退款申请')
                ->descriptionIcon('heroicon-o-arrow-uturn-left')
                ->color('gray'),

            Stat::make('待处理退款', Refund::where('status', RefundStatus::Pending)->count())
                ->description('需要处理的退款')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('danger'),
        ];
    }
}
