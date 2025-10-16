<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\JobBatches;

use App\Filament\Backend\Clusters\Settings\Resources\JobBatches\Tables\JobBatchesTable;
use App\Filament\Backend\Clusters\Settings\SettingsCluster;
use App\Models\JobBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class JobBatchResource extends Resource
{
    protected static ?string $model = JobBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $modelLabel = '批处理队列';

    protected static ?string $navigationLabel = '批处理队列';

    protected static string|UnitEnum|null $navigationGroup = '维护';

    protected static ?int $navigationSort = 102;

    public static function table(Table $table): Table
    {
        return JobBatchesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageJobBatches::route('/'),
        ];
    }
}
