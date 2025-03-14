<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources;

use App\Filament\Backend\Clusters\Tenants\Resources\StafferResource\Pages\ManageStaffers;
use App\Filament\Backend\Clusters\Tenants;
use App\Models\Administrator;
use Filament\Resources\Resource;

class StafferResource extends Resource
{
    protected static ?string $model = Administrator::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $cluster = Tenants::class;

    protected static ?string $modelLabel = '团队成员';

    protected static ?string $navigationLabel = '团队成员';

    protected static ?int $navigationSort = 2;

    public static function getPages(): array
    {
        return [
            'index' => ManageStaffers::route('/'),
        ];
    }
}
