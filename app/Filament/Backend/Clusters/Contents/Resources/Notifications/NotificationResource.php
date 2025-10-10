<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\Notifications;

use App\Filament\Backend\Clusters\Contents\ContentsCluster;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class NotificationResource extends Resource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $cluster = ContentsCluster::class;

    protected static ?string $modelLabel = '数据库通知';

    protected static ?string $navigationLabel = '数据库通知管理';

    protected static ?int $navigationSort = 20;

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageNotifications::route('/'),
        ];
    }
}
