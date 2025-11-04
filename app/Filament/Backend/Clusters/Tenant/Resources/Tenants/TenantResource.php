<?php

namespace App\Filament\Backend\Clusters\Tenant\Resources\Tenants;

use App\Filament\Backend\Clusters\Tenant\TenantCluster;
use App\Models\Tenant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::GlobeAlt;

    protected static ?string $cluster = TenantCluster::class;

    protected static ?string $modelLabel = '租户';

    protected static ?string $navigationLabel = '租户管理';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return Schemas\TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\TenantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StaffersRelationManager::class,
            RelationManagers\RolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTenants::route('/'),
            'view' => Pages\ViewTenant::route('/{record}'),
        ];
    }
}
