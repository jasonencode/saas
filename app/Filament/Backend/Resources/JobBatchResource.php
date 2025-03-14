<?php

namespace App\Filament\Backend\Resources;

use App\Filament\Backend\Resources\JobBatchResource\Pages\ManageJobBatches;
use App\Models\JobBatch;
use Filament\Resources\Resource;

class JobBatchResource extends Resource
{
    protected static ?string $model = JobBatch::class;

    protected static ?string $modelLabel = '批处理队列';

    protected static ?string $navigationIcon = 'heroicon-m-adjustments-horizontal';

    protected static ?string $navigationGroup = '扩展';

    protected static ?int $navigationSort = 102;

    public static function getPages(): array
    {
        return [
            'index' => ManageJobBatches::route('/'),
        ];
    }
}
