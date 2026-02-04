<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = '订单日志';

    protected static ?string $modelLabel = '订单日志';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('用户'),
                Tables\Columns\TextColumn::make('context')
                    ->label('上下文'),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ]);
    }
}

