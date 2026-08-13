<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Refunds\Tables;

use App\Filament\Tables\Components\UserInfoColumn;
use App\Filament\Tables\Filters\TenantFilter;
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
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('no')
                    ->label('退款单号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.no')
                    ->label('订单号')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('total')
                    ->label('退款金额')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('refund_at')
                    ->label('退款时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\TrashedFilter::make(),
            ]);
    }
}
