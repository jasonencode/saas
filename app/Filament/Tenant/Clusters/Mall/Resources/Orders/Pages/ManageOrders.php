<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Pages;

use App\Enums\Mall\OrderScope;
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
        $tabs = [
            'all' => Tab::make()
                ->label('全部'),
        ];

        foreach (OrderScope::cases() as $tab) {
            $tabs[$tab->value] = Tab::make()
                ->label($tab->getLabel())
                ->badge(fn () => Order::query()->tap(fn (Builder $query) => $tab->apply($query))->count())
                ->modifyQueryUsing(fn (Builder $query) => $tab->apply($query));
        }

        return $tabs;
    }
}
