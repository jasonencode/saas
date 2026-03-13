<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products\Pages;

use App\Enums\ProductStatus;
use App\Filament\Backend\Clusters\Mall\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('全部'),
            'pending' => Tab::make()
                ->label(ProductStatus::Pending->getLabel())
                ->badge(fn() => Product::ofPending()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofPending()),
            'pass' => Tab::make()
                ->label(ProductStatus::Approved->getLabel())
                ->badge(fn() => Product::ofPass()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofPass()),
            'up' => Tab::make()
                ->label(ProductStatus::Up->getLabel())
                ->badge(fn() => Product::ofUp()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofUp()),
            'reject' => Tab::make()
                ->label(ProductStatus::Rejected->getLabel())
                ->badge(fn() => Product::ofReject()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofReject()),
            'down' => Tab::make()
                ->label(ProductStatus::Down->getLabel())
                ->badge(fn() => Product::ofDown()->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->ofDown()),
        ];
    }
}
