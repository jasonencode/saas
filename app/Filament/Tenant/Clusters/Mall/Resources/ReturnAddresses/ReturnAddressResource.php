<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\ReturnAddresses;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\ReturnAddress;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ReturnAddressResource extends Resource
{
    protected static ?string $model = ReturnAddress::class;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '退货地址';

    protected static ?string $navigationLabel = '退货地址管理';

    protected static ?int $navigationSort = 38;

    protected static string|UnitEnum|null $navigationGroup = '基础配置';

    public static function form(Schema $schema): Schema
    {
        return Schemas\ReturnAddressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ReturnAddressesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageReturnAddresses::route('/'),
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
