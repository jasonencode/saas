<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\RechargeOrders\Tables;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\RechargeOrderStatus;
use App\Enums\Finance\RechargeOrderType;
use App\Filament\Tables\Columns\UserInfoColumn;
use App\Filament\Tables\Filters\TenantFilter;
use Filament\Tables;
use Filament\Tables\Table;

class RechargeOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('no')
                    ->label('充值单号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('充值类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('gateway')
                    ->label('支付网关')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('充值金额')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('received_amount')
                    ->label('到账金额')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('支付时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('完成时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('充值类型')
                    ->options(RechargeOrderType::class),
                Tables\Filters\SelectFilter::make('gateway')
                    ->label('支付网关')
                    ->options(PaymentGateway::class),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('backend.status'))
                    ->options(RechargeOrderStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ]);
    }
}
