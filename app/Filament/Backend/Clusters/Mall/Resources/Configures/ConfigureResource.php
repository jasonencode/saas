<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Configures;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use App\Models\Mall\StoreConfigure;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ConfigureResource extends Resource
{
    protected static ?string $model = StoreConfigure::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '店铺配置';

    protected static ?string $navigationLabel = '店铺配置';

    protected static string|null|UnitEnum $navigationGroup = '店铺';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return Schemas\ConfigureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ConfiguresTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageConfigures::route('/'),
            'view' => Pages\ViewConfigure::route('/{record}'),
        ];
    }
}
