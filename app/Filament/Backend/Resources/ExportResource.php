<?php

namespace App\Filament\Backend\Resources;

use App\Filament\Backend\Resources\ExportResource\Pages;
use Filament\Actions\Exports\Models\Export;
use Filament\Resources\Resource;

class ExportResource extends Resource
{
    protected static ?string $model = Export::class;

    protected static ?string $modelLabel = '导出表单';

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?string $navigationGroup = '扩展';

    protected static ?int $navigationSort = 104;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageExports::route('/'),
        ];
    }
}
