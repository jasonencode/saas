<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use App\Models\Order;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('no')
                    ->label('订单编号')
                    ->searchable(),
                TextColumn::make('store.name')
                    ->label('店铺名称')
                    ->searchable(),
                TextColumn::make('total_amount')
                    ->label('订单总额')
                    ->description(fn(Order $record) => $record->amount.' / 运费:'.$record->freight),
                TextColumn::make('status')
                    ->label('状态')
                    ->description(fn(Order $record) => $record->expired_at)
                    ->badge(),
                TextColumn::make('paid_at')
                    ->label('支付时间'),
                TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}

