<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Regions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class RegionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#ID#'),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('上级地区'),
                Tables\Columns\TextColumn::make('name')
                    ->label('地区名称'),
                Tables\Columns\TextColumn::make('level')
                    ->label('地区级别'),
                Tables\Columns\TextColumn::make('sort')
                    ->label('排序'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
