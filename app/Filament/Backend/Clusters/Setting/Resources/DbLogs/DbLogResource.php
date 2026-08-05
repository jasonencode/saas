<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\DbLogs;

use App\Filament\Backend\Clusters\Setting\SettingCluster;
use App\Models\System\DBLog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class DbLogResource extends Resource
{
    protected static ?string $model = DBLog::class;

    protected static ?string $cluster = SettingCluster::class;

    protected static ?string $modelLabel = '日志';

    protected static ?string $navigationLabel = '系统日志';

    protected static string|null|UnitEnum $navigationGroup = '维护';

    protected static ?int $navigationSort = 100;

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\DbLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\DbLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDbLogs::route('/'),
            'view' => Pages\ViewDbLog::route('/{record}'),
        ];
    }
}
