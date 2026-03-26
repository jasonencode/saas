<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Tables;

use App\Filament\Actions\Mall\OrderCancelAction;
use App\Filament\Actions\Mall\OrderPrintPickingListAction;
use App\Filament\Actions\Mall\OrderPrintShippingAction;
use App\Filament\Actions\Mall\OrderShipAction;
use App\Filament\Actions\Mall\OrderSignAction;
use App\Filament\Actions\Mall\OrderVirtualPaymentAction;
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
                Tables\Columns\TextColumn::make('no')
                    ->label('订单编号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('订单总额')
                    ->description(fn (Order $record) => $record->amount.' / 运费:'.$record->freight),
                Tables\Columns\TextColumn::make('skus_quantities')
                    ->label('商品数量')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->badge(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('支付时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                OrderVirtualPaymentAction::make(),
                Actions\ActionGroup::make([
                    OrderCancelAction::make(),
                    OrderSignAction::make(),
                    OrderPrintPickingListAction::make(),
                    OrderPrintShippingAction::make(),
                    OrderShipAction::make(),
                ])
                    ->label('操作')
                    ->link(),
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
