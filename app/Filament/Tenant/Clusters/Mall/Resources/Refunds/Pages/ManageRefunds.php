<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds\Pages;

use App\Enums\Mall\RefundScope;
use App\Filament\Tenant\Clusters\Mall\Resources\Refunds\RefundResource;
use App\Models\Mall\Refund;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageRefunds extends ManageRecords
{
    protected static string $resource = RefundResource::class;

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make()
                ->label('全部'),
        ];

        foreach (RefundScope::cases() as $scope) {
            $tabs[$scope->value] = Tab::make()
                ->label($scope->getLabel())
                ->badge(fn () => Refund::query()->tap(fn (Builder $query) => $scope->apply($query))->count())
                ->modifyQueryUsing(fn (Builder $query) => $scope->apply($query));
        }

        return $tabs;
    }
}
