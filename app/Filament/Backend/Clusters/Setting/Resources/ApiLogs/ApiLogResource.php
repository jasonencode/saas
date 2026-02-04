<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\ApiLogs;

use App\Enums\FilamentPanelGroup;
use App\Filament\Backend\Clusters\Setting\SettingCluster;
use App\Models\ApiLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ApiLogResource extends Resource
{
    protected static ?string $model = ApiLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingCluster::class;

    protected static ?string $modelLabel = 'API记录';

    protected static ?string $navigationLabel = 'API记录';

    protected static string|null|UnitEnum $navigationGroup = FilamentPanelGroup::Api;

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\ApiLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ApiLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageApiLogs::route('/'),
            'view' => Pages\ViewApiLog::route('/{record}'),
        ];
    }
}
