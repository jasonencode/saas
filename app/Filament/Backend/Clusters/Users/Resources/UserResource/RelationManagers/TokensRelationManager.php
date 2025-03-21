<?php

namespace App\Filament\Backend\Clusters\Users\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TokensRelationManager extends RelationManager
{
    protected static string $relationship = 'tokens';

    protected static ?string $title = 'Tokens';

    protected static ?string $modelLabel = 'Token';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn(Builder $query) => $query->orderByDesc('last_used_at'))
            ->columns([
                Tables\Columns\TextColumn::make('tokenable.username')
                    ->label('用户')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('名称'),
                Tables\Columns\TextColumn::make('abilities')
                    ->label('权限'),
                Tables\Columns\TextColumn::make('last_used_at')
                    ->label('最后使用时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ]);
    }
}
