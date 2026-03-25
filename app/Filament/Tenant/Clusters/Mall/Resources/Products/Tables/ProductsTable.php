<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Products\Tables;

use App\Filament\Actions\Common\UpgradeSortAction;
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
                Tables\Columns\TextColumn::make('categories.name')
                    ->label('分类')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('品牌名称')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('stocks')
                    ->label('库存'),
                Tables\Columns\TextColumn::make('sales')
                    ->label('销量'),
                Tables\Columns\TextColumn::make('views')
                    ->label('浏览'),
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('backend.sort')),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->badge(),
            ])
            ->filters([
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
                ])
                    ->link()
                    ->label('操作'),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
