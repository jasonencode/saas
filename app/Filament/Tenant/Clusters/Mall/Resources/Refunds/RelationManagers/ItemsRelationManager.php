<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Number;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = '退款商品';

    protected static ?string $modelLabel = '退款商品';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('orderItem.orderable_name')
                    ->label('商品名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('退款单价')
                    ->formatStateUsing(fn ($state) => Number::currency($state, 'CNY')),
                Tables\Columns\TextColumn::make('qty')
                    ->label('退款数量'),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('小计金额')
                    ->state(fn ($record) => Number::currency($record->price * $record->qty, 'CNY')),
                Tables\Columns\TextColumn::make('remark')
                    ->label('备注')
                    ->placeholder('-'),
            ]);
    }
}
