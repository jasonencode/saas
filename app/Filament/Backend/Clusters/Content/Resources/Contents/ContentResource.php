<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Contents;

use App\Filament\Backend\Clusters\Content\ContentCluster;
use App\Models\Content;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $cluster = ContentCluster::class;

    protected static ?string $modelLabel = '内容';

    protected static ?string $navigationLabel = '内容管理';

    protected static ?int $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = '内容';

    public static function form(Schema $schema): Schema
    {
        return Schemas\ContentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\ContentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ContentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContents::route('/'),
            'view' => Pages\ViewContent::route('/{record}'),
            'create' => Pages\CreateContent::route('/create'),
            'edit' => Pages\EditContent::route('/{record}/edit'),
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
