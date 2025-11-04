<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Exports;

use App\Filament\Backend\Clusters\Setting\SettingCluster;
use BackedEnum;
use Filament\Actions\Exports\Models\Export;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ExportResource extends Resource
{
    protected static ?string $model = Export::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingCluster::class;

    protected static ?string $modelLabel = '表单导出';

    protected static ?string $navigationLabel = '表单导出';

    protected static string|UnitEnum|null $navigationGroup = '维护';

    protected static ?int $navigationSort = 104;

    public static function table(Table $table): Table
    {
        return Tables\ExportsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageExports::route('/'),
        ];
    }
}
