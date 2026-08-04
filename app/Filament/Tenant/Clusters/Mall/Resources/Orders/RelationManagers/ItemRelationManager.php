<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class ItemRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = '订单商品';

    protected static ?string $modelLabel = '订单商品';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('orderable_name')
                    ->label('商品名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('单价')
                    ->formatStateUsing(fn ($state) => Number::currency($state, 'CNY')),
                Tables\Columns\TextColumn::make('qty')
                    ->label('数量'),
                Tables\Columns\TextColumn::make('sub_total')
                    ->label('小计金额')
                    ->formatStateUsing(fn ($state) => Number::currency($state, 'CNY')),
                Tables\Columns\TextColumn::make('remark')
                    ->label('备注'),
            ]);
    }
}
