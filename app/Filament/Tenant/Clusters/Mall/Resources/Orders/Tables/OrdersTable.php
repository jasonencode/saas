<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Tables;

use App\Enums\Mall\FulfillmentType;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundStatus;
use App\Filament\Actions\Mall\CreateRefundAction;
use App\Filament\Actions\Mall\OrderAddRemarkAction;
use App\Filament\Actions\Mall\OrderBulkPrintPickingListAction;
use App\Filament\Actions\Mall\OrderCancelAction;
use App\Filament\Actions\Mall\OrderCancelDeliverAction;
use App\Filament\Actions\Mall\OrderCompleteAction;
use App\Filament\Actions\Mall\OrderDelaySignAction;
use App\Filament\Actions\Mall\OrderModifyAddressAction;
use App\Filament\Actions\Mall\OrderPaymentAction;
use App\Filament\Actions\Mall\OrderPreparingAction;
use App\Filament\Actions\Mall\OrderPrintPickingListAction;
use App\Filament\Actions\Mall\OrderShipAction;
use App\Filament\Actions\Mall\OrderSignAction;
use App\Filament\Actions\Mall\OrderUpdateExpressAction;
use App\Filament\Actions\Mall\OrderUpdateFreightAction;
use App\Filament\Actions\Mall\OrderVerifyAction;
use App\Filament\Tables\Columns\UserInfoColumn;
use App\Models\Mall\Order;
use App\Services\Mall\OrderService;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use RuntimeException;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['items.refundItems.refund', 'refunds']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('no')
                    ->label('订单编号')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('items_quantity')
                    ->label('SKU数')
                    ->numeric(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('订单总额')
                    ->money('CNY')
                    ->description(fn (Order $record) => '￥'.$record->amount.' / 运费:￥'.$record->freight.(
                        bccomp((string) $record->coupon_discount, '0', 2) === 1
                            ? ' / 券抵扣:-￥'.$record->coupon_discount
                            : ''
                    ))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->description(fn (Order $record) => $record->expired_at)
                    ->badge(),
                Tables\Columns\TextColumn::make('fulfillment_type')
                    ->label('履约方式')
                    ->badge()
                    ->state(fn (Order $record): ?string => $record->fulfillment_type?->getLabel())
                    ->color(fn (Order $record): ?string => $record->fulfillment_type?->getColor())
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('refund_status')
                    ->label('退款状态')
                    ->badge()
                    ->state(function (Order $record): ?string {
                        // 退款单已随列表预载，避免逐行查询
                        $activeRefund = $record->refunds
                            ->sortByDesc('id')
                            ->first(fn ($refund): bool => in_array($refund->status, [
                                RefundStatus::Pending,
                                RefundStatus::WaitingReturn,
                                RefundStatus::Shipping,
                                RefundStatus::Processing,
                            ], true));

                        return $activeRefund?->status?->getLabel();
                    }),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('支付时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('下单时间')
                    ->dateTime()
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
                Tables\Filters\SelectFilter::make('fulfillment_type')
                    ->label('履约方式')
                    ->options(FulfillmentType::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                CreateRefundAction::make(),
                Actions\ActionGroup::make([
                    OrderVerifyAction::make(),
                    OrderPreparingAction::make(),
                    OrderPrintPickingListAction::make(),
                    OrderShipAction::make(),
                    OrderSignAction::make(),
                    OrderDelaySignAction::make(),
                    OrderUpdateExpressAction::make(),
                    OrderCancelDeliverAction::make(),
                    OrderUpdateFreightAction::make(),
                    OrderCompleteAction::make(),
                    OrderPaymentAction::make(),
                    OrderCancelAction::make(),
                    OrderModifyAddressAction::make(),
                    OrderAddRemarkAction::make(),
                    Actions\DeleteAction::make()
                        ->visible(fn (Order $record): bool => in_array($record->status, OrderStatus::deletable(), true) && userCan('delete', $record))
                        ->action(function (Order $record): void {
                            try {
                                service(OrderService::class)->delete($record, Filament::auth()->user());

                                $this->successNotificationTitle('订单已删除');
                                $this->success();
                            } catch (InvalidArgumentException|RuntimeException $e) {
                                $this->failureNotificationTitle('删除失败');
                                $this->failureNotificationBody($e->getMessage());
                                $this->failure();
                            }
                        }),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    OrderBulkPrintPickingListAction::make(),
                    Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records): void {
                            // 逐条走领域服务：释放库存/优惠券/秒杀名额后再删除
                            $records->each(
                                fn (Order $record) => service(OrderService::class)->delete($record, Filament::auth()->user())
                            );
                        }),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
