<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\FailedJobs;

use App\Filament\Backend\Clusters\Setting\SettingCluster;
use App\Models\FailedJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FailedJobResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingCluster::class;

    protected static ?string $modelLabel = '失败队列';

    protected static ?string $navigationLabel = '失败队列';

    protected static string|null|UnitEnum $navigationGroup = '维护';

    protected static ?int $navigationSort = 103;

    public static function table(Table $table): Table
    {
        return Tables\FailedJobsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFailedJobs::route('/'),
        ];
    }
}
