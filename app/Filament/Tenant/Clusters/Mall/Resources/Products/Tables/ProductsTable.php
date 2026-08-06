<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Products\Tables;

use App\Enums\Mall\ProductStatus;
use App\Filament\Actions\Common\UpgradeSortAction;
use App\Filament\Actions\Mall\ProductBulkDeliveryAction;
use App\Filament\Actions\Mall\ProductBulkDownAction;
use App\Filament\Actions\Mall\ProductBulkUpAction;
use App\Filament\Actions\Mall\ProductDownAction;
use App\Filament\Actions\Mall\ProductUpAction;
use App\Filament\Actions\Mall\ProductUpgradeViewsAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->bySort())
            ->columns([
                Tables\Columns\ImageColumn::make('cover')
                    ->label('封面图'),
                Tables\Columns\TextColumn::make('name')
                    ->label('商品名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('分类')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('品牌')
                    ->searchable()
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('供应商')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')
                    ->label('价格')
                    ->prefix('¥'),
                Tables\Columns\TextColumn::make('total_stock')
                    ->label('库存'),
                Tables\Columns\TextColumn::make('total_sale')
                    ->label('销量'),
                Tables\Columns\TextColumn::make('views')
                    ->label('浏览')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('backend.sort'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('backend.status'))
                    ->options(ProductStatus::class),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('分类')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('品牌')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('供应商')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\ActionGroup::make([
                    ProductUpAction::make(),
                    ProductDownAction::make(),
                    ProductUpgradeViewsAction::make(),
                    UpgradeSortAction::make(),
                    Actions\ViewAction::make(),
                    Actions\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    ProductBulkUpAction::make(),
                    ProductBulkDownAction::make(),
                    ProductBulkDeliveryAction::make(),
                    Actions\DeleteBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
