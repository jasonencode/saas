<?php

namespace App\Filament\Backend\Clusters\Finannce\Resources\Vouchers;

use App\Filament\Backend\Clusters\Finannce\FinannceCluster;
use App\Models\Voucher;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = FinannceCluster::class;

    protected static ?string $modelLabel = '结算凭据';

    protected static string|UnitEnum|null $navigationGroup = '结算';

    protected static ?int $navigationSort = 1;

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
