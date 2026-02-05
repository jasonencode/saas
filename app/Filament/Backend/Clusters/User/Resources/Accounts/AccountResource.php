<?php

namespace App\Filament\Backend\Clusters\User\Resources\Accounts;

use App\Enums\FilamentPanelGroup;
use App\Filament\Backend\Clusters\User\Resources\Accounts\Schemas\AccountForm;
use App\Filament\Backend\Clusters\User\Resources\Accounts\Schemas\AccountInfolist;
use App\Filament\Backend\Clusters\User\Resources\Accounts\Tables\AccountsTable;
use App\Filament\Backend\Clusters\User\UserCluster;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $modelLabel = '账户';

    protected static ?string $navigationLabel = '用户账户';

    protected static ?int $navigationSort = 88;

    protected static string|UnitEnum|null $navigationGroup = FilamentPanelGroup::Account;

    public static function form(Schema $schema): Schema
    {
        return AccountForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountsTable::configure($table);
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
            'index' => Pages\ListAccounts::route('/'),
            'view' => Pages\ViewAccount::route('/{record}'),
        ];
    }
}
