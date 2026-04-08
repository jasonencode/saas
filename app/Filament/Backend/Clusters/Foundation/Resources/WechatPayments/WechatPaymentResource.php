<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\WechatPayments;

use App\Filament\Backend\Clusters\Foundation\FoundationCluster;
use App\Models\Foundation\WechatPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class WechatPaymentResource extends Resource
{
    protected static ?string $model = WechatPayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = FoundationCluster::class;

    protected static ?string $modelLabel = '微信支付';

    protected static ?string $navigationLabel = '微信支付';

    protected static ?int $navigationSort = 2;

    protected static string|UnitEnum|null $navigationGroup = '微信';

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
