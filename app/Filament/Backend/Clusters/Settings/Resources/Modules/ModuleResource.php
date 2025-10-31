<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\Modules;

use App\Filament\Backend\Clusters\Settings\SettingsCluster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ModuleResource extends Resource
{
    protected static ?string $cluster = SettingsCluster::class;

    protected static string|null|BackedEnum $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = '模块';

    protected static ?string $navigationLabel = '模块管理';

    protected static string|null|UnitEnum $navigationGroup = '维护';

    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return Schemas\ModuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ModulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageModules::route('/'),
        ];
    }
}
