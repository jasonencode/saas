<?php

namespace App\Admin\Resources;

use App\Admin\Resources\FailedJobResource\Pages\ManageFailedJobs;
use App\Models\FailedJob;
use Filament\Resources\Resource;

class FailedJobResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    protected static ?string $modelLabel = '失败队列';

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $navigationGroup = '扩展';

    protected static ?int $navigationSort = 103;

    public static function getPages(): array
    {
        return [
            'index' => ManageFailedJobs::route('/'),
        ];
    }
}
