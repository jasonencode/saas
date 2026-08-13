<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds\Tables;

use App\Enums\Mall\RefundStatus;
use App\Filament\Actions\Mall\ApproveRefundAction;
use App\Filament\Actions\Mall\ApproveRefundBulkAction;
use App\Filament\Actions\Mall\CancelRefundAction;
use App\Filament\Actions\Mall\ConfirmReceiveAction;
use App\Filament\Actions\Mall\ConfirmRefundAction;
use App\Filament\Actions\Mall\ConfirmRefundBulkAction;
use App\Filament\Actions\Mall\RejectRefundAction;
use App\Filament\Actions\Mall\RejectRefundBulkAction;
use App\Filament\Actions\Mall\ShipReturnAction;
use App\Filament\Tables\Components\UserInfoColumn;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class RefundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('no')
                    ->label('退款单号')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('order.no')
                    ->label('订单号')
                    ->copyable()
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('type')
                    ->label('退款类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('退款原因')
                    ->badge(),
                Tables\Columns\TextColumn::make('total')
                    ->label('退款金额')
                    ->money('CNY')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->badge(),
                Tables\Columns\TextColumn::make('refund_at')
                    ->label('退款时间')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('申请时间')
                    ->sortable(),
            ])
            ->searchPlaceholder('搜索退款单号')
            ->filters([
                DateRangeFilter::make('created_at')
                    ->teleport()
                    ->timePicker()
                    ->timePicker24()
                    ->timePickerSecond()
                    ->timePickerIncrement()
                    ->allowInput()
                    ->format('Y-m-d H:i:s')
                    ->label('申请时间'),
                DateRangeFilter::make('refund_at')
                    ->teleport()
                    ->timePicker()
                    ->timePicker24()
                    ->timePickerSecond()
                    ->timePickerIncrement()
                    ->allowInput()
                    ->format('Y-m-d H:i:s')
                    ->label('退款时间'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    ApproveRefundAction::make(),
                    RejectRefundAction::make(),
                    ShipReturnAction::make(),
                    ConfirmReceiveAction::make(),
                    ConfirmRefundAction::make(),
                    CancelRefundAction::make(),
                    Actions\DeleteAction::make()
                        ->visible(fn ($record): bool => in_array($record->status, [RefundStatus::Rejected, RefundStatus::Cancelled], true)),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    ApproveRefundBulkAction::make(),
                    RejectRefundBulkAction::make(),
                    ConfirmRefundBulkAction::make(),
                    Actions\DeleteBulkAction::make()
                        ->visible(fn (HasTable $livewire): bool => in_array($livewire->activeTab, ['cancelled', 'rejected'], true)),
                ]),
            ]);
    }
}
