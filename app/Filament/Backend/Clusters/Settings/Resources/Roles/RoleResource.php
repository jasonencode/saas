<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\Roles;

use App\Filament\Backend\Clusters\Settings\SettingsCluster;
use App\Models\AdminRole;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = AdminRole::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?string $modelLabel = '角色';

    protected static ?string $navigationLabel = '角色管理';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return Schemas\RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRoles::route('/'),
        ];
    }
}
