<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Categories;

use App\Filament\Backend\Clusters\Content\ContentCluster;
use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $cluster = ContentCluster::class;

    protected static ?string $modelLabel = '分类';

    protected static ?string $navigationLabel = '分类管理';

    protected static ?int $navigationSort = 1;

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
