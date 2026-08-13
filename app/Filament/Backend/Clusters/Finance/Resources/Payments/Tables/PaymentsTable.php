<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Payments\Tables;

use App\Enums\Finance\PaymentGateway;
use App\Enums\Finance\PaymentStatus;
use App\Filament\Tables\Components\UserInfoColumn;
use App\Filament\Tables\Filters\TenantFilter;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsTable
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
                Tables\Columns\TextColumn::make('paymentable.title')
                    ->label('支付对象')
                    ->badge()
                    ->color('amber')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('gateway')
                    ->label('支付网关')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('支付金额')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('支付时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('过期时间')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\SelectFilter::make('gateway')
                    ->label('支付网关')
                    ->options(PaymentGateway::class),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('backend.status'))
                    ->options(PaymentStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ]);
    }
}
