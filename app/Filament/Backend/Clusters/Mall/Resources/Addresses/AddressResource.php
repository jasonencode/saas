<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Addresses;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use App\Models\Address;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class AddressResource extends Resource
{
    protected static ?string $model = Address::class;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '用户地址';

    protected static ?string $navigationLabel = '地址管理';

    protected static ?int $navigationSort = 35;

    protected static string|UnitEnum|null $navigationGroup = '基础配置';

    public static function form(Schema $schema): Schema
    {
        return Schemas\AddressForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\AddressesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAddresses::route('/'),
        ];
    }
}
