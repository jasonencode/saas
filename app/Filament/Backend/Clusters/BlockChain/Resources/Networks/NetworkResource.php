<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Networks;

use App\Filament\Backend\Clusters\BlockChain\BlockChainCluster;
use App\Models\Network;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NetworkResource extends Resource
{
    protected static ?string $model = Network::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = BlockChainCluster::class;

    protected static ?string $modelLabel = '主网';

    protected static ?string $navigationLabel = '主网管理';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return Schemas\NetworkForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\NetworkInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\NetworksTable::configure($table);
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
            'index' => Pages\ManageNetworks::route('/'),
            'view' => Pages\ViewNetwork::route('/{record}'),
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
