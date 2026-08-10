<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Vouchers;

use App\Filament\Tenant\Clusters\Finance\FinanceCluster;
use App\Models\Finance\Voucher;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?string $modelLabel = '结算凭据';

    protected static ?string $navigationLabel = '结算凭据';

    protected static ?int $navigationSort = 61;

    protected static string|UnitEnum|null $navigationGroup = '结算';

    public static function canAccess(): bool
    {
        return FinanceCluster::canAccess();
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\VoucherInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\VouchersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVouchers::route('/'),
            'view' => Pages\ViewVoucher::route('/{record}'),
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
