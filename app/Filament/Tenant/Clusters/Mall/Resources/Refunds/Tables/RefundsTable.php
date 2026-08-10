<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds\Tables;

use App\Enums\Mall\RefundStatus;
use App\Filament\Actions\Mall\CancelRefundAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class RefundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('退款单号')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('order.no')
                    ->label('订单号')
                    ->copyable()
                    ->searchable(),
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
                Tables\Filters\TrashedFilter::make(),
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
            ])
            ->recordActions([
                CancelRefundAction::make(),
                Actions\ViewAction::make(),
                Actions\DeleteAction::make()
                    ->visible(fn ($record): bool => in_array($record->status, [RefundStatus::Rejected, RefundStatus::Cancelled], true)),
            ]);
    }
}
