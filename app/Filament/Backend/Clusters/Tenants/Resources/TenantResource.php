<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources;

use App\Filament\Backend\Clusters\Tenants;
use App\Filament\Backend\Clusters\Tenants\Resources\TenantResource\Pages;
use App\Filament\Backend\Clusters\Tenants\Resources\TenantResource\RelationManagers;
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\AdministratorsRelationManager::make(),
            RelationManagers\RolesRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTenant::route('/'),
            'view' => Pages\ViewTenant::route('/{record}'),
        ];
    }
}
