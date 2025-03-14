<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources\StafferResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'records';

    protected static ?string $modelLabel = '登录记录';

    protected static ?string $title = '登录记录';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                TextColumn::make('ip')
                    ->label('IP'),
                TextColumn::make('user_agent')
                    ->label('Header'),
                TextColumn::make('created_at')
                    ->translateLabel(),
            ]);
    }
}
