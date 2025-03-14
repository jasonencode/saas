<?php

namespace App\Filament\Backend\Resources;

use App\Filament\Backend\Resources\ImportResource\Pages\ManageImports;
use Filament\Actions\Imports\Models\Import;
use Filament\Resources\Resource;

class ImportResource extends Resource
{
    protected static ?string $model = Import::class;

    protected static ?string $modelLabel = '导入表单';

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $navigationGroup = '扩展';

    protected static ?int $navigationSort = 105;

    public static function getPages(): array
    {
        return [
            'index' => ManageImports::route('/'),
        ];
    }
}
