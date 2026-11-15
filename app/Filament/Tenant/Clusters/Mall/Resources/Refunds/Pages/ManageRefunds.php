<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds\Pages;

use App\Enums\Mall\RefundScope;
use App\Filament\Tenant\Clusters\Mall\Resources\Refunds\RefundResource;
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
                ->badge(function () use ($scope): int {
                    // 复用资源查询以获得租户作用域，避免徽标统计到其他租户的退款单
                    $query = RefundResource::getEloquentQuery();
                    $scope->apply($query);

                    return $query->count();
                })
                ->modifyQueryUsing(fn (Builder $query) => $scope->apply($query));
        }

        return $tabs;
    }
}
