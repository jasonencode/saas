<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $modelLabel = '日志';

    protected static ?string $title = '订单日志';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID'),
                Tables\Columns\TextColumn::make('order_id')->label('订单ID'),
                Tables\Columns\TextColumn::make('message')->label('消息'),
            ]);
    }
}