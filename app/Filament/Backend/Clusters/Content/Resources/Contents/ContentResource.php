<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Contents;

use App\Filament\Backend\Clusters\Content\ContentCluster;
use App\Models\Content;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $cluster = ContentCluster::class;

    protected static ?string $modelLabel = '内容';

    protected static ?string $navigationLabel = '内容管理';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return Schemas\ContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\ContentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContents::route('/'),
            'create' => Pages\CreateContent::route('/create'),
            'edit' => Pages\EditContent::route('/{record}/edit'),
        ];
    }
}
