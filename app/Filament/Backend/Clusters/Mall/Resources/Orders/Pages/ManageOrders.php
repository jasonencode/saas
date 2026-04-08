<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Orders\Pages;

use App\Enums\Mall\OrderStatus;
use App\Filament\Backend\Clusters\Mall\Resources\Orders\OrderResource;
use App\Models\Mall\Order;
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
                ->badge(fn () => Order::ofPending()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofPending()),
            'paid' => Tab::make()
                ->label(OrderStatus::Paid->getLabel())
                ->badge(fn () => Order::ofReadyToShip()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofReadyToShip()),
            'delivered' => Tab::make()
                ->label(OrderStatus::Delivered->getLabel())
                ->badge(fn () => Order::ofDelivering()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofDelivering()),
            'signed' => Tab::make()
                ->label(OrderStatus::Signed->getLabel())
                ->badge(fn () => Order::ofSigned()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofSigned()),
            'completed' => Tab::make()
                ->label(OrderStatus::Completed->getLabel())
                ->badge(fn () => Order::ofCompleted()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofCompleted()),
        ];
    }
}

