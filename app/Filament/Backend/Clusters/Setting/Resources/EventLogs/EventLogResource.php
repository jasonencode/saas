<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\EventLogs;

use App\Filament\Backend\Clusters\Setting\SettingCluster;
use App\Models\System\EventLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * 日志审计
 *
 * 订单、退款等业务事件统一记录在 event_logs，后台只读展示。
 */
class EventLogResource extends Resource
{
    protected static ?string $model = EventLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $cluster = SettingCluster::class;

    protected static ?string $modelLabel = '事件日志';

    protected static ?string $navigationLabel = '事件日志';

    protected static string|null|UnitEnum $navigationGroup = '维护';

    protected static ?int $navigationSort = 99;

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\EventLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\EventLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageEventLogs::route('/'),
            'view' => Pages\ViewEventLog::route('/{record}'),
        ];
    }
}
