<?php

namespace App\Filament\Backend\Clusters\User\Resources\Realnames;

use App\Filament\Backend\Clusters\User\UserCluster;
use App\Models\User\UserRealname;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RealnameResource extends Resource
{
    protected static ?string $model = UserRealname::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $modelLabel = '实名认证';

    protected static ?string $navigationLabel = '实名认证';

    protected static ?int $navigationSort = 50;

    protected static string|UnitEnum|null $navigationGroup = '身份';

    public static function form(Schema $schema): Schema
    {
        return Schemas\RealnameForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\RealnamesTable::configure($table);
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
            'index' => Pages\ManageRealnames::route('/'),
            'view' => Pages\ViewRealname::route('/{record}'),
        ];
    }
}
