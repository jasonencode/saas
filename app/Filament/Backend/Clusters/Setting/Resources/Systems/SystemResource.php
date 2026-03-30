<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Systems;

use App\Filament\Backend\Clusters\Setting\Resources\Systems\Pages\ManageSystems;
use App\Filament\Backend\Clusters\Setting\Resources\Systems\Schemas\SystemForm;
use App\Filament\Backend\Clusters\Setting\Resources\Systems\Tables\SystemsTable;
use App\Filament\Backend\Clusters\Setting\SettingCluster;
use App\Models\System;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SystemResource extends Resource
{
    protected static ?string $model = System::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingCluster::class;

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationLabel = '系统用户';

    protected static string|null|UnitEnum $navigationGroup = '维护';

    protected static ?int $navigationSort = 114;

    public static function form(Schema $schema): Schema
    {
        return SystemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SystemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSystems::route('/'),
        ];
    }
}
