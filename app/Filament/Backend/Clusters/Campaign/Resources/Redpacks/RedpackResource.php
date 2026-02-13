<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Redpacks;

use App\Filament\Backend\Clusters\Campaign\CampaignCluster;
use App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Pages\ManageRedpacks;
use App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Pages\ViewRedpack;
use App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Schemas\RedpackForm;
use App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Schemas\RedpackInfolist;
use App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Tables\RedpacksTable;
use App\Models\Redpack;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RedpackResource extends Resource
{
    protected static ?string $model = Redpack::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = CampaignCluster::class;

    protected static ?string $modelLabel = '红包活动';

    protected static ?string $navigationLabel = '红包活动';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return Schemas\RedpackForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\RedpackInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\RedpacksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CodesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRedpacks::route('/'),
            'view' => ViewRedpack::route('/{record}'),
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
