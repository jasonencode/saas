<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Refunds;

use App\Filament\Tenant\Clusters\Finance\FinanceCluster;
use App\Models\Finance\PaymentRefund;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RefundResource extends Resource
{
    protected static ?string $model = PaymentRefund::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyYen;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $modelLabel = '退款';

    protected static ?string $navigationLabel = '退款订单';

    protected static ?int $navigationSort = 2;

    protected static string|UnitEnum|null $navigationGroup = '订单';

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\RefundInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\RefundsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRefunds::route('/'),
            'view' => Pages\ViewRefund::route('/{record}'),
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
