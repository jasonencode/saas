<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $modelLabel = '日志';

    protected static ?string $title = '操作日志';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('操作用户'),
                Tables\Columns\TextColumn::make('records')
                    ->label('事件内容'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at')),
            ]);
    }
}
