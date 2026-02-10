<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Addresses;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use App\Models\Address;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AddressResource extends Resource
{
    protected static ?string $model = Address::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '用户地址';

    protected static ?string $navigationLabel = '地址管理';

    protected static ?int $navigationSort = 3;

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
