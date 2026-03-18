<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Contracts;

use App\Filament\Backend\Clusters\BlockChain\BlockChainCluster;
use App\Models\Contract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static ?string $cluster = BlockChainCluster::class;

    protected static ?string $modelLabel = '合约';

    protected static ?string $navigationLabel = '智能合约';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return Schemas\ContractForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\ContractInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ContractsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'view' => Pages\ViewContract::route('/{record}'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
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
