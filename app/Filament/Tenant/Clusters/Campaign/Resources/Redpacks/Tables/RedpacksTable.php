<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Redpacks\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class RedpacksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('活动名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_at')
                    ->label('开始时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_at')
                    ->label('结束时间')
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
