<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\BlackLists\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class BlackListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP地址')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remark')
                    ->label('备注')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
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
