<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\ScheduleRunLogs;

use App\Filament\Backend\Clusters\Setting\SettingCluster;
use App\Models\System\ScheduleRunLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ScheduleRunLogResource extends Resource
{
    protected static ?string $model = ScheduleRunLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $cluster = SettingCluster::class;

    protected static ?string $modelLabel = '任务日志';

    protected static ?string $navigationLabel = '任务日志';

    protected static string|null|UnitEnum $navigationGroup = '维护';

    protected static ?int $navigationSort = 101;

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\ScheduleRunLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ScheduleRunLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageScheduleRunLogs::route('/'),
            'view' => Pages\ViewScheduleRunLog::route('/{record}'),
        ];
    }
}
