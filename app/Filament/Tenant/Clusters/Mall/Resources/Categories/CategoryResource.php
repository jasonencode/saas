<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Categories;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '分类';

    protected static ?string $navigationLabel = '分类管理';

    protected static ?int $navigationSort = 1;

    protected static bool $isScopedToTenant = false;

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

