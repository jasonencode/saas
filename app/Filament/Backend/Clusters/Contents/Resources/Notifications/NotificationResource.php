<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\Notifications;

use App\Filament\Backend\Clusters\Contents\ContentsCluster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
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
        return Tables\DatabaseNotificationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageNotifications::route('/'),
        ];
    }
}
