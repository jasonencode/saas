<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\BlackLists\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlackListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP地址')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remark')
                    ->label('备注')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
