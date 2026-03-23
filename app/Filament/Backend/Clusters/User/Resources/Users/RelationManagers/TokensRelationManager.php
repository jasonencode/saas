<?php

namespace App\Filament\Backend\Clusters\User\Resources\Users\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TokensRelationManager extends RelationManager
{
    protected static string $relationship = 'tokens';

    protected static ?string $title = '登录凭证';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('abilities')
                    ->label('权限')
                    ->badge()
                    ->separator(','),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('最后使用')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('过期时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
