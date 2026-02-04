<?php

namespace App\Filament\Tenant\Clusters\Setting\Resources\Staffers;

use App\Enums\AdminType;
use App\Filament\Backend\Clusters\Setting\Resources\Administrators\Schemas\AdministratorForm;
use App\Filament\Backend\Clusters\Setting\Resources\Administrators\Tables\AdministratorsTable;
use App\Filament\Tenant\Clusters\Setting\SettingCluster;
use App\Models\Administrator;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class StafferResource extends Resource
{
    protected static ?string $model = Administrator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingCluster::class;

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return AdministratorForm::configure($schema, AdminType::Tenant);
    }

    public static function table(Table $table): Table
    {
        return AdministratorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStaffers::route('/'),
        ];
    }
}
