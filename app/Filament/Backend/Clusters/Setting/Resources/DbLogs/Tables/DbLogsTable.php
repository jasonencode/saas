<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Tables;

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
                    ->color(fn (string $state): string => match ($state) {
                        'DEBUG' => 'gray',
                        'INFO' => 'info',
                        'NOTICE' => 'info',
                        'WARNING' => 'warning',
                        'ERROR' => 'danger',
                        'CRITICAL' => 'danger',
                        'ALERT' => 'danger',
                        'EMERGENCY' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('message')
                    ->label('消息')
                    ->limit(50)
                    ->tooltip(fn ($record): string => $record->message)
                    ->searchable(),
                Tables\Columns\TextColumn::make('datetime')
                    ->label('时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('context')
                    ->label('上下文')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->label('通道')
                    ->options([
                        'stack' => 'Stack',
                        'single' => 'Single',
                        'daily' => 'Daily',
                        'slack' => 'Slack',
                        'database' => 'Database',
                        'syslog' => 'Syslog',
                        'errorlog' => 'Errorlog',
                        'emergency' => 'Emergency',
                    ]),
                Tables\Filters\SelectFilter::make('level_name')
                    ->label('级别')
                    ->options([
                        'DEBUG' => 'Debug',
                        'INFO' => 'Info',
                        'NOTICE' => 'Notice',
                        'WARNING' => 'Warning',
                        'ERROR' => 'Error',
                        'CRITICAL' => 'Critical',
                        'ALERT' => 'Alert',
                        'EMERGENCY' => 'Emergency',
                    ]),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
