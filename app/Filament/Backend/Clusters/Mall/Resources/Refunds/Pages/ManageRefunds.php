<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Refunds\Pages;

use App\Enums\Mall\RefundScope;
use App\Filament\Backend\Clusters\Mall\Resources\Refunds\RefundResource;
use App\Models\Mall\Refund;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class ManageRefunds extends ManageRecords
{
    protected static string $resource = RefundResource::class;

    public function getTabs(): array
    {
        $cacheKey = 'mall:backend:refund_tabs:'.auth()->id();
        $ttl = now()->addMinutes(5);

        $counts = Cache::remember($cacheKey, $ttl, static function () {
            $result = [];
            foreach (RefundScope::cases() as $scope) {
                $query = Refund::query();
                $scope->apply($query);
                $result[$scope->value] = $query->count();
            }

            return $result;
        });

        $tabs = [
            'all' => Tab::make()
                ->label('全部'),
        ];

        foreach (RefundScope::cases() as $scope) {
            $tabs[$scope->value] = Tab::make()
                ->label($scope->getLabel())
                ->badge($counts[$scope->value])
                ->modifyQueryUsing(fn (Builder $query) => $scope->apply($query));
        }

        return $tabs;
    }
}
