<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Accounts;

use App\Filament\Tenant\Clusters\Finance\FinanceCluster;
use App\Models\UserAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AccountResource extends Resource
{
    protected static ?string $model = UserAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $modelLabel = '账户';

    protected static ?string $navigationLabel = '用户账户';

    protected static ?int $navigationSort = 30;

    protected static string|UnitEnum|null $navigationGroup = '账户';

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return Schemas\AccountForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\AccountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\AccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAccounts::route('/'),
            'view' => Pages\ViewAccount::route('/{record}'),
        ];
    }
}