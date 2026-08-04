<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Users\Tables;

use Filament\Tables;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('用户UID')
                    ->sortable(),
                Tables\Columns\ImageColumn::make('profile.avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('profile.nickname')
                    ->label('昵称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('identities.name')
                    ->label('身份')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ]);
    }
}
