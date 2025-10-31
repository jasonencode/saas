<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources\Activities;

use App\Filament\Backend\Clusters\Settings\Resources\Activities\Tables\ActivitiesTable;
use App\Filament\Tenant\Clusters\Settings\SettingsCluster;
use App\Models\Activity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $modelLabel = '日志审计';

    protected static string|UnitEnum|null $navigationGroup = '日志';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageActivities::route('/'),
        ];
    }
}
