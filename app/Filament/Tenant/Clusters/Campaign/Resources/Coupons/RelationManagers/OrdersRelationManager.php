<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    protected static ?string $title = '使用订单';

    protected static ?string $modelLabel = '订单';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('订单编号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at')),
            ]);
    }
}

