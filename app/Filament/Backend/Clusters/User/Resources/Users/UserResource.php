<?php

namespace App\Filament\Backend\Clusters\User\Resources\Users;

use App\Filament\Backend\Clusters\User\UserCluster;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return Schemas\UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RecordsRelationManager::class,
            RelationManagers\TokensRelationManager::class,
            RelationManagers\IdentityLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
