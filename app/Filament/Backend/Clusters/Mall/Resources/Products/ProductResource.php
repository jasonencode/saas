<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '商品';

    protected static ?string $navigationLabel = '商品管理';

    protected static ?int $navigationSort = 2;

    protected static string|UnitEnum|null $navigationGroup = '商品';

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\ProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ProductsTable::configure($table);
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
            'index' => Pages\ListProducts::route('/'),
            'view' => Pages\ViewProduct::route('/{record}'),
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

