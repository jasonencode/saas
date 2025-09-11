<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\Contents;

use App\Filament\Backend\Clusters\Contents\ContentsCluster;
use App\Filament\Backend\Clusters\Contents\Resources\Contents\Pages\CreateContent;
use App\Filament\Backend\Clusters\Contents\Resources\Contents\Pages\EditContent;
use App\Filament\Backend\Clusters\Contents\Resources\Contents\Pages\ListContents;
use App\Filament\Backend\Clusters\Contents\Resources\Contents\Schemas\ContentForm;
use App\Filament\Backend\Clusters\Contents\Resources\Contents\Tables\ContentsTable;
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

    protected static ?string $cluster = ContentsCluster::class;

    protected static ?string $modelLabel = '内容';

    protected static ?string $navigationLabel = '内容管理';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContents::route('/'),
            'create' => CreateContent::route('/create'),
            'edit' => EditContent::route('/{record}/edit'),
        ];
    }
}
