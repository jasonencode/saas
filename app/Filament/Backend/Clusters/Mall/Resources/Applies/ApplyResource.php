<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Applies;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use App\Models\StoreApply;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ApplyResource extends Resource
{
    protected static ?string $model = StoreApply::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '申请';

    protected static ?string $navigationLabel = '店铺申请';

    protected static string|null|UnitEnum $navigationGroup = '店铺';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\ApplyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\AppliesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageApplies::route('/'),
            'view' => Pages\ViewApply::route('/{record}'),
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
