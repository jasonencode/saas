<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\Tenants\RelationManagers;

use App\Filament\Backend\Clusters\Settings\Resources\Roles\Tables\RolesTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class RolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    protected static ?string $modelLabel = '角色';

    protected static ?string $title = '角色管理';

    public function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }
}
