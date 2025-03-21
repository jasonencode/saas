<?php

namespace App\Filament\Backend\Clusters\Contents\Resources;

use App\Filament\Backend\Clusters\Contents;
use App\Filament\Backend\Clusters\Contents\Resources\NotificationResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class NotificationResource extends Resource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static ?string $modelLabel = '数据库通知';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationLabel = '通知管理';

    protected static ?int $navigationSort = 20;

    protected static ?string $cluster = Contents::class;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                Tables\Columns\TextColumn::make('notifiable')
                    ->label('通知对象')
                    ->getStateUsing(fn(DatabaseNotification $record) => $record->notifiable->name)
                    ->description(fn(DatabaseNotification $record) => $record->notifiable->username),
                Tables\Columns\TextColumn::make('title')
                    ->label('通知标题')
                    ->getStateUsing(fn(DatabaseNotification $record) => $record->data['title']),
                Tables\Columns\TextColumn::make('read_at')
                    ->label('阅读时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('发送时间'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageNotifications::route('/'),
        ];
    }
}
