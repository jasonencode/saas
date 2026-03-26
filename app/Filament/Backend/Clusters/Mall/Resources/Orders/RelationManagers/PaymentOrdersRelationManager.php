<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentOrders';

    protected static ?string $title = '支付记录';

    protected static ?string $modelLabel = '支付记录';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('支付单号')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('支付金额')
                    ->money('CNY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('gateway')
                    ->label('支付方式')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('支付状态')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('付款用户'),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('支付时间')
                    ->placeholder('未支付'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
            ]);
    }
}
