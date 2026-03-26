<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Refunds;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Refund;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class RefundResource extends Resource
{
    protected static ?string $model = Refund::class;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '售后';

    protected static ?string $navigationLabel = '售后管理';

    protected static string|null|UnitEnum $navigationGroup = '订单';

    protected static ?int $navigationSort = 21;

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
}

