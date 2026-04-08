<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Addresses;

use App\Filament\Backend\Clusters\BlockChain\BlockChainCluster;
use App\Models\BlockChain\ChainAddress;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AddressResource extends Resource
{
    protected static ?string $model = ChainAddress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $cluster = BlockChainCluster::class;

    protected static ?string $modelLabel = '地址';

    protected static ?string $navigationLabel = '地址管理';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return Schemas\AddressForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\AddressInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\AddressesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAddresses::route('/'),
            'view' => Pages\ViewAddress::route('/{record}'),
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
