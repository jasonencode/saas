<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\Activities;

use App\Filament\Backend\Clusters\Settings\SettingsCluster;
use App\Models\Activity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
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
        return Tables\ActivitiesTable::configure($table)
            ->columns([
                TextColumn::make('log_name')
                    ->label('平台'),
                ...$table->getColumns(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageActivities::route('/'),
        ];
    }
}
