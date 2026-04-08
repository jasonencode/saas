<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Suppliers;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use App\Models\Mall\Supplier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '供应商';

    protected static ?int $navigationSort = 13;

    protected static string|null|UnitEnum $navigationGroup = '商品';

    public static function form(Schema $schema): Schema
    {
        return Schemas\SupplierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\SuppliersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSuppliers::route('/'),
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
