<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Lotteries;

use App\Filament\Tenant\Clusters\Campaign\CampaignCluster;
use App\Models\Campaign\Lottery;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LotteryResource extends Resource
{
    protected static ?string $model = Lottery::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $cluster = CampaignCluster::class;

    protected static ?string $modelLabel = '抽奖活动';

    protected static ?string $navigationLabel = '抽奖活动';

    protected static string|UnitEnum|null $navigationGroup = '抽奖活动';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return Schemas\LotteryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\LotteryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\LotteriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PrizesRelationManager::class,
            RelationManagers\DrawsRelationManager::class,
            RelationManagers\PrizeRecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLotteries::route('/'),
            'view' => Pages\ViewLottery::route('/{record}'),
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
