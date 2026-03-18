<?php

namespace App\Filament\Backend\Clusters\Content\Resources\AppVersions;

use App\Filament\Backend\Clusters\Content\ContentCluster;
use App\Models\AppVersion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AppVersionResource extends Resource
{
    protected static ?string $model = AppVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $cluster = ContentCluster::class;

    protected static ?string $modelLabel = '版本';

    protected static ?string $navigationLabel = 'APP版本管理';

    protected static string|UnitEnum|null $navigationGroup = '系统';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return Schemas\AppVersionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\AppVersionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAppVersions::route('/'),
        ];
    }
}
