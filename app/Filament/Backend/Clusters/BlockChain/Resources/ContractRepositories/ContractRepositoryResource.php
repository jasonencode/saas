<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories;

use App\Filament\Backend\Clusters\BlockChain\BlockChainCluster;
use App\Models\BlockChain\ContractRepository;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ContractRepositoryResource extends Resource
{
    protected static ?string $model = ContractRepository::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $cluster = BlockChainCluster::class;

    protected static ?string $modelLabel = '合约仓库';

    protected static ?string $navigationLabel = '合约仓库';

    protected static ?int $navigationSort = 4;

    protected static string|UnitEnum|null $navigationGroup = '区块链';

    public static function form(Schema $schema): Schema
    {
        return Schemas\ContractRepositoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\ContractRepositoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ContractRepositoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContractRepositories::route('/'),
            'create' => Pages\CreateContractRepository::route('/create'),
            'view' => Pages\ViewContractRepository::route('/{record}'),
            'edit' => Pages\EditContractRepository::route('/{record}/edit'),
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
