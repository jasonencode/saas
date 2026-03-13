<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Notifications;

use App\Filament\Backend\Clusters\Content\ContentCluster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Notifications\DatabaseNotification;
use UnitEnum;

class NotificationResource extends Resource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $cluster = ContentCluster::class;

    protected static ?string $modelLabel = '数据库通知';

    protected static ?string $navigationLabel = '数据库通知管理';

    protected static ?int $navigationSort = 20;

    protected static string|null|UnitEnum $navigationGroup = '系统';

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
