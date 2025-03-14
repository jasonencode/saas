<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources;

use App\Filament\Backend\Clusters\Tenants;
use App\Models\Tenant;
use Filament\Resources\Resource;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $cluster = Tenants::class;

    protected static ?string $modelLabel = '租户';

    protected static ?string $navigationLabel = '租户管理';

    protected static ?int $navigationSort = 1;

    public static function getPages(): array
    {
        return [
            'index' => Tenants\Resources\TenantResource\Pages\ManageTenant::route('/'),
        ];
    }
}
