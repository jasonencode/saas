<?php

namespace App\Filament\Tables\Campaign;

use App\Enums\Campaign\SeckillActivityStatus;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class SeckillActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('租户')
                    ->visible(fn (): bool => filament()->getTenant() === null)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('活动名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge(),
                Tables\Columns\TextColumn::make('sessions_count')
                    ->label('场次数')
                    ->counts('sessions'),
                Tables\Columns\TextColumn::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options(SeckillActivityStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make(),
                    Actions\EditAction::make(),
                    Actions\DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
