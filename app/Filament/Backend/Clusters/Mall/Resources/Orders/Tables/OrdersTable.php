<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('租户名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('no')
                    ->label('订单编号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('订单总额')
                    ->description(fn(Order $record) => $record->amount.' / 运费:'.$record->freight),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->description(fn(Order $record) => $record->expired_at)
                    ->badge(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('支付时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label(__('backend.tenant'))
                    ->relationship(
                        name: 'tenant',
                        titleAttribute: 'name'
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
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

