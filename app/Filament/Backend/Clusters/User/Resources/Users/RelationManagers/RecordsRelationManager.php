<?php

namespace App\Filament\Backend\Clusters\User\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'records';

    protected static ?string $title = '登录记录';

    protected static ?string $modelLabel = '登录记录';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ip')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('ip')
                    ->label('登录IP'),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('头信息'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('登录时间')
                    ->sortable(),
            ]);
    }
}
