<?php

namespace App\Filament\Backend\Clusters\Users\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'records';

    protected static ?string $title = '登录记录';

    protected static ?string $modelLabel = '登录记录';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ip')
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                Tables\Columns\TextColumn::make('ip')
                    ->label('登录IP'),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('头信息'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('登录时间'),
            ]);
    }
}
