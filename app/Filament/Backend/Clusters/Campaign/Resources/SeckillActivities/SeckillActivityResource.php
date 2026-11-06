<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\SeckillActivities;

use App\Filament\Backend\Clusters\Campaign\CampaignCluster;
use App\Filament\Forms\Campaign\SeckillActivityForm;
use App\Filament\Infolists\Campaign\SeckillActivityInfolist;
use App\Filament\RelationManagers\Campaign\SeckillSessionsRelationManager;
use App\Filament\Tables\Campaign\SeckillActivitiesTable;
use App\Models\Campaign\SeckillActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SeckillActivityResource extends Resource
{
    protected static ?string $model = SeckillActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $cluster = CampaignCluster::class;

    protected static ?string $modelLabel = '秒杀活动';

    protected static ?string $navigationLabel = '秒杀活动';

    protected static string|null|UnitEnum $navigationGroup = '秒杀';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return SeckillActivityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SeckillActivityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SeckillActivitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SeckillSessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSeckillActivities::route('/'),
            'view' => Pages\ViewSeckillActivity::route('/{record}'),
        ];
    }
}
