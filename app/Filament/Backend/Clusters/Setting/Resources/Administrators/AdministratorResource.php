<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Administrators;

use App\Enums\AdminType;
use App\Filament\Backend\Clusters\Setting\SettingCluster;
use App\Models\Administrator;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdministratorResource extends Resource
{
    protected static ?string $model = Administrator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $cluster = SettingCluster::class;

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return Schemas\AdministratorForm::configure($schema, AdminType::Admin);
    }

    public static function table(Table $table): Table
    {
        return Tables\AdministratorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAdministrators::route('/'),
        ];
    }
}
