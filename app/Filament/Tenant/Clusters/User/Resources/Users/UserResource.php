<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Users;

use App\Filament\Tenant\Clusters\User\UserCluster;
use App\Models\User\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = '用户';

    protected static ?string $tenantOwnershipRelationshipName = 'tenants';

    public static function canAccess(): bool
    {
        return UserCluster::canAccess();
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
