<?php

namespace App\Filament\Backend\Clusters\Mall\Widgets;

use App\Enums\Mall\ProductStatus;
use App\Enums\Mall\RefundStatus;
use App\Filament\Backend\Clusters\Mall\Resources\Orders\OrderResource;
use App\Filament\Backend\Clusters\Mall\Resources\Products\ProductResource;
use App\Filament\Backend\Clusters\Mall\Resources\Refunds\RefundResource;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Refund;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('商品总数', Product::count())
                ->description('所有状态的商品')
                ->descriptionIcon(Heroicon::OutlinedArchiveBox)
                ->color('info')
                ->url(ProductResource::getIndexUrl()),

            Stat::make('上架商品', Product::where('status', ProductStatus::Up)->count())
                ->description('正在销售的商品')
                ->descriptionIcon(Heroicon::OutlinedShoppingBag)
                ->color('success')
                ->url(ProductResource::getIndexUrl(['tab' => 'up'])),

            Stat::make('订单总数', Order::count())
                ->description('所有订单')
                ->descriptionIcon(Heroicon::OutlinedShoppingCart)
                ->color('warning')
                ->url(OrderResource::getIndexUrl()),

            Stat::make('待发货订单', Order::ofReadyToShip()->count())
                ->description('需要发货的订单')
                ->descriptionIcon(Heroicon::OutlinedTruck)
                ->color('danger')
                ->url(OrderResource::getIndexUrl(['tab' => 'paid'])),

            Stat::make('售后单总数', Refund::count())
                ->description('所有售后申请')
                ->descriptionIcon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->url(RefundResource::getIndexUrl()),

            Stat::make('待处理售后', Refund::where('status', RefundStatus::Pending)->count())
                ->description('需要处理的售后')
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle)
                ->color('danger')
                ->url(RefundResource::getIndexUrl(['tab' => 'pending'])),
        ];
    }
}
