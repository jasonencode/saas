<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\RechargeOrders;

use App\Filament\Backend\Clusters\Finance\FinanceCluster;
use App\Models\Finance\RechargeOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RechargeOrderResource extends Resource
{
    protected static ?string $model = RechargeOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $modelLabel = '充值订单';

    protected static ?string $navigationLabel = '充值订单';

    protected static ?int $navigationSort = 5;

    protected static string|UnitEnum|null $navigationGroup = '订单';

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\RechargeOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\RechargeOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRechargeOrders::route('/'),
            'view' => Pages\ViewRechargeOrder::route('/{record}'),
        ];
    }
}
