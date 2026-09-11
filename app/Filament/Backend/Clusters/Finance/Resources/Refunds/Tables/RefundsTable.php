<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Refunds\Tables;

use App\Enums\Finance\PaymentRefundStatus;
use App\Filament\Actions\Finance\ApprovePaymentRefundAction;
use App\Filament\Actions\Finance\ExecutePaymentRefundAction;
use App\Filament\Actions\Finance\RejectPaymentRefundAction;
use App\Filament\Actions\Finance\RetryPaymentRefundAction;
use App\Filament\Tables\Columns\UserInfoColumn;
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
                    ->badge(),
                Tables\Columns\TextColumn::make('no')
                    ->label('退款单号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('paymentOrder.no')
                    ->label('支付单号')
                    ->searchable()
                    ->placeholder('-'),
                UserInfoColumn::make('created_by')
                    ->label('申请人'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('退款金额')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->badge()
                    ->sortable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('refunded_at')
                    ->label('退款完成时间')
                    ->dateTime()
                    ->sortable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('审核人')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('审核时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('failed_reason')
                    ->label('失败原因')
                    ->limit(30)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('backend.status'))
                    ->options(PaymentRefundStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make(),
                    ApprovePaymentRefundAction::make(),
                    RejectPaymentRefundAction::make(),
                    ExecutePaymentRefundAction::make(),
                    RetryPaymentRefundAction::make(),
                    Actions\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
