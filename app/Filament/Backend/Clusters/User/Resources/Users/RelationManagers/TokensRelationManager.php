<?php

namespace App\Filament\Backend\Clusters\User\Resources\Users\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
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
                TextColumn::make('name')
                    ->label('名称')
                    ->searchable(),
                TextColumn::make('abilities')
                    ->label('权限')
                    ->badge()
                    ->separator(','),
                TextColumn::make('last_used_at')
                    ->label('最后使用')
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('过期时间')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
