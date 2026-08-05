<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Mall\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '订单';

    protected static ?string $navigationLabel = '订单管理';

    protected static ?int $navigationSort = 10;

    protected static string|UnitEnum|null $navigationGroup = '订单';

    public static function canAccess(): bool
    {
        return MallCluster::isAvailable();
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemRelationManager::class,
            RelationManagers\ShippingsRelationManager::class,
            RelationManagers\LogsRelationManager::class,
        ];
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
            'index' => Pages\ManageOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
