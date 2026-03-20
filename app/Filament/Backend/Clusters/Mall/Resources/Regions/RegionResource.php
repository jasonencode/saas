<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Regions;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use App\Models\Region;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RegionResource extends Resource
{
    protected static ?string $model = Region::class;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '行政区划';

    protected static ?string $navigationLabel = '行政区划';

    protected static string|null|UnitEnum $navigationGroup = '基础配置';

    protected static ?int $navigationSort = 35;

    public static function form(Schema $schema): Schema
    {
        return Schemas\RegionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\RegionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRegions::route('/'),
        ];
    }
}
