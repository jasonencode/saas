<?php

namespace App\Filament\Backend\Clusters\Finannce\Resources\PaymentOrders\Tables;

use App\Filament\Tables\Components\UserInfoColumn;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PaymentOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                UserInfoColumn::make(),
                TextColumn::make('no')
                    ->label('支付单号')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
