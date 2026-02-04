<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = '订单商品';

    protected static ?string $modelLabel = '订单商品';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('商品名称'),
                Tables\Columns\TextColumn::make('sku_id')
                    ->label('商品SKU'),
                Tables\Columns\TextColumn::make('sku.code')
                    ->label('69码'),
                Tables\Columns\TextColumn::make('qty')
                    ->label('数量'),
                Tables\Columns\TextColumn::make('price')
                    ->label('单价'),
                Tables\Columns\TextColumn::make('sub_total')
                    ->money('CNY')
                    ->label('小计金额'),
                Tables\Columns\TextColumn::make('remark')
                    ->label('备注'),
            ]);
    }
}

