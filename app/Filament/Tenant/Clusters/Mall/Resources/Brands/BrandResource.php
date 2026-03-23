<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Brands;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Filament\Tenant\Clusters\Mall\Resources\Brands\Pages\ManageBrands;
use App\Filament\Tenant\Clusters\Mall\Resources\Brands\Schemas\BrandForm;
use App\Filament\Tenant\Clusters\Mall\Resources\Brands\Tables\BrandsTable;
use App\Models\Brand;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
        return BrandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrandsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBrands::route('/'),
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
