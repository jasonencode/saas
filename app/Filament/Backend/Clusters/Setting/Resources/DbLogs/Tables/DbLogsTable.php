<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Tables;

use App\Enums\System\LogLevel;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class DbLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('channel')
                    ->label('通道'),
                Tables\Columns\TextColumn::make('level_name')
                    ->label('级别')
                    ->badge()
                    ->color(fn (LogLevel $state): string => $state->getColor()),
                Tables\Columns\TextColumn::make('message')
                    ->label('消息')
                    ->limit(50)
                    ->tooltip(fn ($record): string => $record->message)
                    ->searchable(),
                Tables\Columns\TextColumn::make('datetime')
                    ->label('时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level_name')
                    ->label('级别')
                    ->options(LogLevel::class),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
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
