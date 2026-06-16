<?php

namespace App\Filament\Tenant\Clusters\Mall\Widgets;

use App\Enums\Mall\ProductStatus;
use App\Enums\Mall\RefundStatus;
use App\Filament\Tenant\Clusters\Mall\Resources\Orders\OrderResource;
use App\Filament\Tenant\Clusters\Mall\Resources\Products\ProductResource;
use App\Filament\Tenant\Clusters\Mall\Resources\Refunds\RefundResource;
use App\Models\Mall\Order;
use App\Models\Mall\Product;
use App\Models\Mall\Refund;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $tenantId = Filament::getTenant()?->getKey();
        $cacheKey = "mall:tenant:stats:{$tenantId}";
        $ttl = now()->addMinutes(5);

        $stats = Cache::remember($cacheKey, $ttl, function () use ($tenantId) {
            return [
                'product_total' => Product::where('tenant_id', $tenantId)->count(),
                'product_up' => Product::where('tenant_id', $tenantId)->where('status', ProductStatus::Up)->count(),
                'order_total' => Order::where('tenant_id', $tenantId)->count(),
                'order_ready_to_ship' => Order::where('tenant_id', $tenantId)->ofReadyToShip()->count(),
                'refund_total' => Refund::where('tenant_id', $tenantId)->count(),
                'refund_pending' => Refund::where('tenant_id', $tenantId)->where('status', RefundStatus::Pending)->count(),
            ];
        });

        return [
            Stat::make('商品总数', $stats['product_total'])
                ->description('所有状态的商品')
                ->descriptionIcon(Heroicon::OutlinedArchiveBox)
                ->color('info')
                ->url(ProductResource::getIndexUrl()),

            Stat::make('上架商品', $stats['product_up'])
                ->description('正在销售的商品')
                ->descriptionIcon(Heroicon::OutlinedShoppingBag)
                ->color('success')
                ->url(ProductResource::getIndexUrl(['tab' => 'up'])),

            Stat::make('订单总数', $stats['order_total'])
                ->description('所有订单')
                ->descriptionIcon(Heroicon::OutlinedShoppingCart)
                ->color('warning')
                ->url(OrderResource::getIndexUrl()),

            Stat::make('待发货订单', $stats['order_ready_to_ship'])
                ->description('需要发货的订单')
                ->descriptionIcon(Heroicon::OutlinedTruck)
                ->color('danger')
                ->url(OrderResource::getIndexUrl(['tab' => 'paid'])),

            Stat::make('售后单总数', $stats['refund_total'])
                ->description('所有售后申请')
                ->descriptionIcon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->url(RefundResource::getIndexUrl()),

            Stat::make('待处理售后', $stats['refund_pending'])
                ->description('需要处理的售后')
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle)
                ->color('danger')
                ->url(RefundResource::getIndexUrl()),
        ];
    }
}
