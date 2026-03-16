<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\WechatPayments;

use App\Filament\Tenant\Clusters\Foundation\FoundationCluster;
use App\Models\WechatPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WechatPaymentResource extends Resource
{
    protected static ?string $model = WechatPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyYen;

    protected static ?string $cluster = FoundationCluster::class;

    protected static ?string $modelLabel = '微信支付';

    protected static ?string $navigationLabel = '微信支付';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return Schemas\WechatPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\WechatPaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWechatPayments::route('/'),
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
