<?php

namespace App\Admin\Clusters\Contents\Resources;

use App\Admin\Clusters\Contents;
use App\Admin\Clusters\Contents\Resources\NotificationResource\Pages\ManageNotifications;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class NotificationResource extends Resource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static ?string $modelLabel = '数据库通知';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationLabel = '数据库通知管理';

    protected static ?int $navigationSort = 20;

    protected static ?string $cluster = Contents::class;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                TextColumn::make('notifiable')
                    ->label('通知对象')
                    ->getStateUsing(fn(DatabaseNotification $record) => $record->notifiable->name)
                    ->description(fn(DatabaseNotification $record) => $record->notifiable->username),
                TextColumn::make('title')
                    ->label('通知标题')
                    ->getStateUsing(fn(DatabaseNotification $record) => $record->data['title']),
                TextColumn::make('read_at')
                    ->label('阅读时间'),
                TextColumn::make('created_at')
                    ->label('发送时间'),
            ])
            ->filters([
                //
            ])
            ->actions([
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageNotifications::route('/'),
        ];
    }
}
