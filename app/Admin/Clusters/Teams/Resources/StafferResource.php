<?php

namespace App\Admin\Clusters\Teams\Resources;

use App\Admin\Clusters\Teams;
use App\Admin\Clusters\Teams\Resources\StafferResource\Pages\ManageStaffers;
use App\Models\Staffer;
use Filament\Resources\Resource;

class StafferResource extends Resource
{
    protected static ?string $model = Staffer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $cluster = Teams::class;

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
