<?php

namespace App\Filament\Tenant\Clusters\Setting\Resources\Roles;

use App\Filament\Backend\Clusters\Setting\Resources\Roles\Schemas\RoleForm;
use App\Filament\Backend\Clusters\Setting\Resources\Roles\Tables\RolesTable;
use App\Filament\Tenant\Clusters\Setting\SettingCluster;
use App\Models\AdminRole;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = AdminRole::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = SettingCluster::class;

    protected static ?string $modelLabel = '角色';

    protected static ?string $navigationLabel = '角色管理';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoles::route('/'),
        ];
    }
}
