<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;

class UserExporter extends BaseExporter
{
    protected static ?string $model = User::class;

    public static function getName(): string
    {
        return '用户';
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('tenant.name')
                ->label(__('backend.tenant')),
            ExportColumn::make('username')
                ->label('用户名'),
        ];
    }
}
