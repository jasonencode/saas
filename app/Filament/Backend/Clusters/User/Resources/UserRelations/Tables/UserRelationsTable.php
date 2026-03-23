<?php

namespace App\Filament\Backend\Clusters\User\Resources\UserRelations\Tables;

use Filament\Tables;
use Filament\Tables\Table;

class UserRelationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->label('用户ID'),
                Tables\Columns\TextColumn::make('user.profile.nickname')
                    ->label('用户')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.profile.nickname')
                    ->label('推荐用户')
                    ->searchable(),
                Tables\Columns\TextColumn::make('layer')
                    ->label('层级'),
                Tables\Columns\TextColumn::make('path')
                    ->label('路径'),
                Tables\Columns\TextColumn::make('direct_count')
                    ->label('直推用户'),
                Tables\Columns\TextColumn::make('team_count')
                    ->label('团队用户'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at')),
            ])
            ->filters([
            ])
            ->recordActions([
            ]);
    }
}
