<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\DbLogs;

use App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Pages\CreateDbLog;
use App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Pages\EditDbLog;
use App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Pages\ManageDbLogs;
use App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Pages\ViewDbLog;
use App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Schemas\DbLogForm;
use App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Schemas\DbLogInfolist;
use App\Filament\Backend\Clusters\Setting\Resources\DbLogs\Tables\DbLogsTable;
use App\Filament\Backend\Clusters\Setting\SettingCluster;
use App\Models\System\DBLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DbLogResource extends Resource
{
    protected static ?string $model = DBLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingCluster::class;

    public static function form(Schema $schema): Schema
    {
        return DbLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DbLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DbLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDbLogs::route('/'),
            'create' => CreateDbLog::route('/create'),
            'view' => ViewDbLog::route('/{record}'),
            'edit' => EditDbLog::route('/{record}/edit'),
        ];
    }
}
