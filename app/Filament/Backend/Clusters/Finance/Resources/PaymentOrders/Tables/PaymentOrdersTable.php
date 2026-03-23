<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\PaymentOrders\Tables;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Filament\Tables\Components\UserInfoColumn;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentOrdersTable
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
                    ->label('支付单号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('paymentable')
                    ->label('支付对象')
                    ->badge(),
                Tables\Columns\TextColumn::make('gateway')
                    ->label('支付网关')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('支付金额')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('status')
                    ->label('支付状态')
                    ->badge(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('支付时间'),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('过期时间')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gateway')
                    ->label('支付网关')
                    ->options(PaymentGateway::class),
                Tables\Filters\SelectFilter::make('status')
                    ->label('支付状态')
                    ->options(PaymentStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
            ]);
    }
}
