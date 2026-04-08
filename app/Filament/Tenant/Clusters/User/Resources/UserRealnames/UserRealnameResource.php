<?php

namespace App\Filament\Tenant\Clusters\User\Resources\UserRealnames;

use App\Filament\Tenant\Clusters\User\UserCluster;
use App\Models\User\UserRealname;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserRealnameResource extends Resource
{
    protected static ?string $model = UserRealname::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $modelLabel = '实名认证';

    protected static ?string $navigationLabel = '实名认证';

    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return Schemas\UserRealnameForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\UserRealnamesTable::configure($table);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUserRealnames::route('/'),
            'view' => Pages\ViewUserRealname::route('/{record}'),
        ];
    }
}
