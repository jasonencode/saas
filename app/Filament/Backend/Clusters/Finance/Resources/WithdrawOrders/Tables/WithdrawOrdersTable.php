<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\WithdrawOrders\Tables;

use App\Enums\Finance\WithdrawGateway;
use App\Enums\Finance\WithdrawOrderStatus;
use App\Filament\Tables\Components\UserInfoColumn;
use App\Filament\Tables\Filters\TenantFilter;
use Filament\Tables;
use Filament\Tables\Table;

class WithdrawOrdersTable
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
                    ->label('提现单号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('gateway')
                    ->label('提现方式')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('提现金额')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('fee')
                    ->label('手续费')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('actual_amount')
                    ->label('实际到账')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('审核时间')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('打款时间')
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
                    ->label('提现方式')
                    ->options(WithdrawGateway::class),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('backend.status'))
                    ->options(WithdrawOrderStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ]);
    }
}
