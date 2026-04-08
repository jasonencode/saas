<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Brands;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use App\Models\Mall\Brand;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '品牌';

    protected static ?string $navigationLabel = '品牌管理';

    protected static string|null|UnitEnum $navigationGroup = '基础配置';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return Schemas\BrandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\BrandsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBrands::route('/'),
        ];
    }
}
