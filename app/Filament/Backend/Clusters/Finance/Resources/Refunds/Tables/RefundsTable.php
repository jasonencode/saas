<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Refunds\Tables;

use App\Enums\Finance\PaymentRefundStatus;
use App\Filament\Tables\Components\UserInfoColumn;
use App\Filament\Tables\Filters\TenantFilter;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class RefundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('no')
                    ->label('退款单号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('paymentOrder.no')
                    ->label('支付单号')
                    ->searchable(),
                UserInfoColumn::make('created_by')
                    ->label('申请人'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('退款金额')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('status')
                    ->label('退款状态')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('refunded_at')
                    ->label('退款完成时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('审核人')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('审核时间')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('退款状态')
                    ->native(false)
                    ->options(PaymentRefundStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
