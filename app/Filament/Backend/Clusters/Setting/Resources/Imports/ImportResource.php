<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Imports;

use App\Enums\FilamentPanelGroup;
use App\Filament\Backend\Clusters\Setting\SettingCluster;
use BackedEnum;
use Filament\Actions\Imports\Models\Import;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingCluster::class;

    protected static ?string $modelLabel = '表单导入';

    protected static ?string $navigationLabel = '表单导入';

    protected static string|null|UnitEnum $navigationGroup = FilamentPanelGroup::Support;

    protected static ?int $navigationSort = 105;

    public static function table(Table $table): Table
    {
        return Tables\ImportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\MangeImports::route('/'),
        ];
    }
}
