<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Tables;

use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundStatus;
use App\Filament\Actions\Mall\CreateRefundAction;
use App\Filament\Actions\Mall\OrderBulkPrintPickingListAction;
use App\Filament\Actions\Mall\OrderCancelAction;
use App\Filament\Actions\Mall\OrderCompleteAction;
use App\Filament\Actions\Mall\OrderPaymentAction;
use App\Filament\Actions\Mall\OrderPreparingAction;
use App\Filament\Actions\Mall\OrderPrintPickingListAction;
use App\Filament\Actions\Mall\OrderShipAction;
use App\Filament\Actions\Mall\OrderSignAction;
use App\Filament\Tables\Components\UserInfoColumn;
use App\Models\Mall\Order;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('订单编号')
                    ->searchable()
                    ->copyable(),
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('SPU数')
                    ->numeric(),
                Tables\Columns\TextColumn::make('items_quantity')
                    ->label('SKU数')
                    ->numeric(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('订单总额')
                    ->money('CNY')
                    ->description(fn (Order $record) => '￥'.$record->amount.' / 运费:￥'.$record->freight)
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->description(fn (Order $record) => $record->expired_at)
                    ->badge(),
                Tables\Columns\TextColumn::make('refund_status')
                    ->label('退款状态')
                    ->badge()
                    ->state(function (Order $record): ?string {
                        $activeRefund = $record->refunds()
                            ->whereIn('status', [
                                RefundStatus::Pending,
                                RefundStatus::WaitingReturn,
                                RefundStatus::Shipping,
                                RefundStatus::Received,
                                RefundStatus::Processing,
                            ])
                            ->latest()
                            ->first();

                        return $activeRefund?->status?->getLabel();
                    }),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('支付时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('下单时间')
                    ->sortable(),
            ])
            ->searchPlaceholder('搜索订单编号')
            ->filters([
                DateRangeFilter::make('created_at')
                    ->teleport()
                    ->timePicker()
                    ->timePicker24()
                    ->timePickerSecond()
                    ->timePickerIncrement()
                    ->allowInput()
                    ->format('Y-m-d H:i:s')
                    ->label('下单时间'),
                DateRangeFilter::make('paid_at')
                    ->teleport()
                    ->timePicker()
                    ->timePicker24()
                    ->timePickerSecond()
                    ->timePickerIncrement()
                    ->allowInput()
                    ->format('Y-m-d H:i:s')
                    ->label('支付时间'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\ActionGroup::make([
                    OrderPreparingAction::make(),
                    OrderPrintPickingListAction::make(),
                    OrderShipAction::make(),
                    OrderSignAction::make(),
                    OrderCompleteAction::make(),
                    OrderPaymentAction::make(),
                    OrderCancelAction::make(),
                    CreateRefundAction::make(),
                    Actions\DeleteAction::make()
                        ->visible(fn (Order $record): bool => in_array($record->status, [OrderStatus::Pending, OrderStatus::Canceled], true)),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    OrderBulkPrintPickingListAction::make(),
                    Actions\DeleteBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
