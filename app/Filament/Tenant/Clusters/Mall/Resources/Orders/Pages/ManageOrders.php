<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Pages;

use App\Filament\Tenant\Clusters\Mall\Resources\Orders\OrderResource;
use App\Models\Mall\Order;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ManageOrders extends ManageRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        $tenantId = Filament::getTenant()?->getKey();
        $cacheKey = "mall:tenant:order_tabs:{$tenantId}";
        $ttl = now()->addMinutes(5);

        $counts = Cache::remember($cacheKey, $ttl, function () use ($tenantId) {
            return [
                'pending' => Order::where('tenant_id', $tenantId)->ofPending()->count(),
                'paid' => Order::where('tenant_id', $tenantId)->ofReadyToShip()->count(),
                'delivered' => Order::where('tenant_id', $tenantId)->ofDelivering()->count(),
                'signed' => Order::where('tenant_id', $tenantId)->ofSigned()->count(),
                'completed' => Order::where('tenant_id', $tenantId)->ofCompleted()->count(),
            ];
        });

        return [
            'all' => Tab::make()
                ->label('全部'),
            'pending' => Tab::make()
                ->label('待付款')
                ->badge($counts['pending'])
                ->modifyQueryUsing(fn (Builder $query) => $query->ofPending()),
            'paid' => Tab::make()
                ->label('待发货')
                ->badge($counts['paid'])
                ->modifyQueryUsing(fn (Builder $query) => $query->ofReadyToShip()),
            'delivered' => Tab::make()
                ->label('已发货')
                ->badge($counts['delivered'])
                ->modifyQueryUsing(fn (Builder $query) => $query->ofDelivering()),
            'signed' => Tab::make()
                ->label('已签收')
                ->badge($counts['signed'])
                ->modifyQueryUsing(fn (Builder $query) => $query->ofSigned()),
            'completed' => Tab::make()
                ->label('已完成')
                ->badge($counts['completed'])
                ->modifyQueryUsing(fn (Builder $query) => $query->ofCompleted()),
        ];
    }
}
