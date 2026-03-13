<?php

namespace App\Filament\Backend\Clusters\User\Resources\SocialiteAccounts;

use App\Filament\Backend\Clusters\User\UserCluster;
use App\Models\SocialiteAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SocialiteAccountResource extends Resource
{
    protected static ?string $model = SocialiteAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $modelLabel = '平台';

    protected static ?string $navigationLabel = '三方平台';

    protected static ?int $navigationSort = 51;

    protected static string|UnitEnum|null $navigationGroup = '社会化登录';

    public static function form(Schema $schema): Schema
    {
        return Schemas\SocialiteAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\SocialiteAccountsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSocialiteAccounts::route('/'),
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
