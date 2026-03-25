<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Order;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '订单';

    protected static ?string $navigationLabel = '订单管理';

    protected static string|null|UnitEnum $navigationGroup = '订单';

    protected static ?int $navigationSort = 20;

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
            RelationManagers\ExpressesRelationManager::class,
            RelationManagers\LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}

