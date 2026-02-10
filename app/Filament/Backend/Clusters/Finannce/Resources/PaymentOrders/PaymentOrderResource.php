<?php

namespace App\Filament\Backend\Clusters\Finannce\Resources\PaymentOrders;

use App\Filament\Backend\Clusters\Finannce\FinannceCluster;
use App\Models\PaymentOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentOrderResource extends Resource
{
    protected static ?string $model = PaymentOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = FinannceCluster::class;

    protected static ?string $modelLabel = '支付订单';

    protected static ?string $navigationLabel = '支付订单';

    protected static ?int $navigationSort = 1;

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\PaymentOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\PaymentOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePaymentOrders::route('/'),
            'view' => Pages\ViewPaymentOrder::route('/{record}'),
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
