<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Categories;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '分类';

    protected static ?string $navigationLabel = '分类管理';

    protected static ?int $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = '商品';

    public static function form(Schema $schema): Schema
    {
        return Schemas\CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\CategoriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCategories::route('/'),
        ];
    }
}

