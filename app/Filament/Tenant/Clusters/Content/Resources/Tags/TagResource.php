<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\Tags;

use App\Filament\Tenant\Clusters\Content\ContentCluster;
use App\Models\Content\ContentTag;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TagResource extends Resource
{
    protected static ?string $model = ContentTag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static ?string $cluster = ContentCluster::class;

    protected static ?string $modelLabel = '标签';

    protected static ?string $navigationLabel = '标签管理';

    protected static ?int $navigationSort = 3;

    protected static string|UnitEnum|null $navigationGroup = '内容';

    public static function canAccess(): bool
    {
        return ContentCluster::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return Schemas\TagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\TagsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTags::route('/'),
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
