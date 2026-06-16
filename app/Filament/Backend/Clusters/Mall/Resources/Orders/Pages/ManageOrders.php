<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Orders\Pages;

use App\Enums\Mall\OrderStatus;
use App\Filament\Backend\Clusters\Mall\Resources\Orders\OrderResource;
use App\Models\Mall\Order;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        $cacheKey = 'mall:backend:order_tabs:'.auth()->id();
        $ttl = now()->addMinutes(5);

        $counts = Cache::remember($cacheKey, $ttl, static function () {
            return [
                'pending' => Order::ofPending()->count(),
                'paid' => Order::ofReadyToShip()->count(),
                'delivered' => Order::ofDelivering()->count(),
                'signed' => Order::ofSigned()->count(),
                'completed' => Order::ofCompleted()->count(),
            ];
        });

        return [
            'all' => Tab::make()
                ->label('全部'),
            'pending' => Tab::make()
                ->label(OrderStatus::Pending->getLabel())
                ->badge($counts['pending'])
                ->modifyQueryUsing(fn(Builder $query) => $query->ofPending()),
            'paid' => Tab::make()
                ->label(OrderStatus::Paid->getLabel())
                ->badge($counts['paid'])
                ->modifyQueryUsing(fn(Builder $query) => $query->ofReadyToShip()),
            'delivered' => Tab::make()
                ->label(OrderStatus::Delivered->getLabel())
                ->badge($counts['delivered'])
                ->modifyQueryUsing(fn(Builder $query) => $query->ofDelivering()),
            'signed' => Tab::make()
                ->label(OrderStatus::Signed->getLabel())
                ->badge($counts['signed'])
                ->modifyQueryUsing(fn(Builder $query) => $query->ofSigned()),
            'completed' => Tab::make()
                ->label(OrderStatus::Completed->getLabel())
                ->badge($counts['completed'])
                ->modifyQueryUsing(fn(Builder $query) => $query->ofCompleted()),
        ];
    }
}
