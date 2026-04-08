<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Payments;

use App\Filament\Tenant\Clusters\Finance\FinanceCluster;
use App\Models\Finance\PaymentOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PaymentResource extends Resource
{
    protected static ?string $model = PaymentOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyYen;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $modelLabel = '支付订单';

    protected static ?string $navigationLabel = '支付订单';

    protected static ?int $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = '订单';

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\PaymentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\PaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
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
