<?php

namespace App\Filament\Backend\Clusters\Contents\Resources;

use App\Filament\Backend\Clusters\Contents;
use App\Filament\Backend\Clusters\Contents\Resources\ExamineResource\Pages\ManageExamines;
use App\Models\Examine;
use Filament\Resources\Resource;

class ExamineResource extends Resource
{
    protected static ?string $model = Examine::class;

    protected static ?string $modelLabel = '易审核';

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = '易审核管理';

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = Contents::class;

    public static function getPages(): array
    {
        return [
            'index' => ManageExamines::route('/'),
        ];
    }
}
