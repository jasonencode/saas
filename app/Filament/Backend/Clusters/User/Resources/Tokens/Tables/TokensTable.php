<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tokens\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Laravel\Sanctum\PersonalAccessToken;

class TokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_used_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tokenable.name')
                    ->label('用户')
                    ->description(fn (PersonalAccessToken $record) => $record->tokenable?->username)
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('名称'),
                Tables\Columns\TextColumn::make('abilities')
                    ->label('权限'),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('最后使用时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->recordActions([
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
