<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Examines;

use App\Filament\Backend\Clusters\Content\ContentCluster;
use App\Models\Examine;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ExamineResource extends Resource
{
    protected static ?string $model = Examine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $cluster = ContentCluster::class;

    protected static ?string $modelLabel = '易审核';

    protected static ?string $navigationLabel = '易审核管理';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return Tables\ExaminesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageExamines::route('/'),
        ];
    }
}
