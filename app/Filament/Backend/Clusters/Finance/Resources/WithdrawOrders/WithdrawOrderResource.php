<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\WithdrawOrders;

use App\Filament\Backend\Clusters\Finance\FinanceCluster;
use App\Models\Finance\WithdrawOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WithdrawOrderResource extends Resource
{
    protected static ?string $model = WithdrawOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $modelLabel = '提现订单';

    protected static ?string $navigationLabel = '提现订单';

    protected static ?int $navigationSort = 6;

    protected static string|UnitEnum|null $navigationGroup = '订单';

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\WithdrawOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\WithdrawOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWithdrawOrders::route('/'),
            'view' => Pages\ViewWithdrawOrder::route('/{record}'),
        ];
    }
}
