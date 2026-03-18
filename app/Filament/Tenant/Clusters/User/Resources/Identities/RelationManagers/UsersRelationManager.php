<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Identities\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $modelLabel = '用户';

    protected static ?string $title = '已订阅用户';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('昵称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('pivot.start_at')
                    ->label('开始时间')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pivot.end_at')
                    ->label('结束时间')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('永久'),
                Tables\Columns\TextColumn::make('pivot.serial')
                    ->label('身份编号')
                    ->placeholder('无'),
                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('订阅时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('pivot.created_at', 'desc');
    }
}