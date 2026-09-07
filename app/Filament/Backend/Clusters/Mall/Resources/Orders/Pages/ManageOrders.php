<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Orders\Pages;

use App\Enums\Mall\OrderScope;
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
            $result = [];
            foreach (OrderScope::cases() as $tab) {
                $query = Order::query();
                $tab->apply($query);
                $result[$tab->value] = $query->count();
            }

            return $result;
        });

        $tabs = [
            'all' => Tab::make()
                ->label('全部'),
        ];

        foreach (OrderScope::cases() as $tab) {
            $tabs[$tab->value] = Tab::make()
                ->label($tab->getLabel())
                ->badge($counts[$tab->value])
                ->modifyQueryUsing(fn (Builder $query) => $tab->apply($query));
        }

        return $tabs;
    }
}
