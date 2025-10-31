<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\Staffers;

use App\Filament\Backend\Clusters\Tenants\TenantsCluster;
use App\Models\Administrator;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StafferResource extends Resource
{
    protected static ?string $model = Administrator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $cluster = TenantsCluster::class;

    protected static ?string $modelLabel = '租户用户';

    protected static ?string $navigationLabel = '租户用户';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return Schemas\StafferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\StaffersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStaffers::route('/'),
        ];
    }
}
