<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Identities\RelationManagers;

use App\Enums\IdentityOrderStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $modelLabel = '订单';

    protected static ?string $title = '订阅订单';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('订单号')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('用户')
                    ->searchable(),
                Tables\Columns\TextColumn::make('qty')
                    ->label('订阅数量'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('订单金额')
                    ->prefix('¥')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('支付状态')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('下单时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('支付状态')
                    ->options(IdentityOrderStatus::class),
            ])
            ->defaultSort('created_at', 'desc');
    }
}