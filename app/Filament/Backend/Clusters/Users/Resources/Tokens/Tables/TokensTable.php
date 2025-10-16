<?php

namespace App\Filament\Backend\Clusters\Users\Resources\Tokens\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\PersonalAccessToken;

class TokensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->orderByDesc('last_used_at'))
            ->columns([
                Tables\Columns\TextColumn::make('tokenable.info.nickname')
                    ->label('用户')
                    ->description(fn(PersonalAccessToken $record) => $record->tokenable?->username)
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('名称'),
                Tables\Columns\TextColumn::make('abilities')
                    ->label('权限'),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('最后使用时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
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
