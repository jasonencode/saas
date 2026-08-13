<?php

namespace App\Filament\Backend\Clusters\User\Resources\UserRelations\Tables;

use App\Filament\Tables\Components\UserInfoColumn;
use Filament\Tables;
use Filament\Tables\Table;

class UserRelationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                UserInfoColumn::make(),
                UserInfoColumn::make('parent')
                    ->label('推荐用户'),
                Tables\Columns\TextColumn::make('layer')
                    ->label('层级'),
                Tables\Columns\TextColumn::make('path')
                    ->label('路径'),
                Tables\Columns\TextColumn::make('direct_count')
                    ->label('直推用户'),
                Tables\Columns\TextColumn::make('team_count')
                    ->label('团队用户'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ]);
    }
}
