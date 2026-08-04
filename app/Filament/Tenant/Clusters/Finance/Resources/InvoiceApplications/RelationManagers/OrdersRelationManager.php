<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\InvoiceApplications\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = '关联订单';

    protected static ?string $modelLabel = '订单';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('订单编号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('订单总额')
                    ->money('cny')
                    ->description(fn ($record) => $record->amount.' / 运费:'.$record->freight),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('支付时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ]);
    }
}
