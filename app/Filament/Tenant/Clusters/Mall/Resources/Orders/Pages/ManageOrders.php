<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Tenant\Clusters\Mall\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('全部'),
            'pending' => Tab::make()
                ->label(OrderStatus::Pending->getLabel())
                ->badge(fn() => Order::ofPending()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofPending()),
            'paid' => Tab::make()
                ->label(OrderStatus::Paid->getLabel())
                ->badge(fn() => Order::ofPaid()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofPaid()),
            'delivered' => Tab::make()
                ->label(OrderStatus::Delivered->getLabel())
                ->badge(fn() => Order::ofDelivered()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofDelivered()),
            'signed' => Tab::make()
                ->label(OrderStatus::Signed->getLabel())
                ->badge(fn() => Order::ofSigned()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofSigned()),
        ];
    }
}

