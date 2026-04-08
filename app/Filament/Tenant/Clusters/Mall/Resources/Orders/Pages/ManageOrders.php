<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Pages;

use App\Filament\Tenant\Clusters\Mall\Resources\Orders\OrderResource;
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
                ->label('待付款')
                ->badge(fn () => Order::ofPending()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofPending()),
            'paid' => Tab::make()
                ->label('待发货')
                ->badge(fn () => Order::ofReadyToShip()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofReadyToShip()),
            'delivered' => Tab::make()
                ->label('已发货')
                ->badge(fn () => Order::ofDelivering()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofDelivering()),
            'signed' => Tab::make()
                ->label('已签收')
                ->badge(fn () => Order::ofSigned()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofSigned()),
            'completed' => Tab::make()
                ->label('已完成')
                ->badge(fn () => Order::ofCompleted()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ofCompleted()),
        ];
    }
//
//    public function getDefaultActiveTab(): string
//    {
//        return 'paid';
//    }
}

