<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Identities;

use App\Filament\Tenant\Clusters\User\UserCluster;
use App\Models\Identity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class IdentityResource extends Resource
{
    protected static ?string $model = Identity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $modelLabel = '身份';

    protected static ?string $navigationLabel = '用户身份';

    protected static ?int $navigationSort = 40;

    protected static string|UnitEnum|null $navigationGroup = '身份';

    public static function form(Schema $schema): Schema
    {
        return Schemas\IdentityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\IdentityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\IdentitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrdersRelationManager::class,
            RelationManagers\UsersRelationManager::class,
            RelationManagers\IdentityLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageIdentities::route('/'),
            'view' => Pages\ViewIdentity::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
