<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Notifications\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('notifiable')
                    ->label('通知对象')
                    ->getStateUsing(fn(DatabaseNotification $record) => $record->notifiable->name)
                    ->description(fn(DatabaseNotification $record) => $record->notifiable->username),
                Tables\Columns\TextColumn::make('title')
                    ->label('通知标题')
                    ->getStateUsing(fn(DatabaseNotification $record) => $record->data['title'] ?? ''),
                Tables\Columns\TextColumn::make('read_at')
                    ->label('阅读时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('发送时间'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
