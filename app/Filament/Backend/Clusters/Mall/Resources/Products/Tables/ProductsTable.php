<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products\Tables;

use App\Enums\Mall\ProductStatus;
use App\Filament\Actions\Common\UpgradeSortAction;
use App\Filament\Actions\Mall\ProductAuditAction;
use App\Filament\Actions\Mall\ProductDownAction;
use App\Filament\Actions\Mall\ProductUpAction;
use App\Filament\Actions\Mall\ProductUpgradeViewsAction;
use App\Filament\Tables\Filters\TenantFilter;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
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
                TenantFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('backend.status'))
                    ->options(ProductStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    ProductAuditAction::make(),
                    ProductUpAction::make(),
                    ProductDownAction::make(),
                    ProductUpgradeViewsAction::make(),
                    UpgradeSortAction::make(),
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
