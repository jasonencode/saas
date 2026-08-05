<?php

namespace App\Filament\Backend\Clusters\Content\Resources\SinglePages;

use App\Filament\Backend\Clusters\Content\ContentCluster;
use App\Models\Content\SinglePage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SinglePageResource extends Resource
{
    protected static ?string $model = SinglePage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $cluster = ContentCluster::class;

    protected static ?string $modelLabel = '单页内容';

    protected static ?string $navigationLabel = '单页管理';

    protected static ?int $navigationSort = 2;

    protected static string|UnitEnum|null $navigationGroup = '内容';

    public static function form(Schema $schema): Schema
    {
        return Schemas\SinglePageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\SinglePageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\SinglePagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSinglePages::route('/'),
            'create' => Pages\CreateSinglePage::route('/create'),
            'edit' => Pages\EditSinglePage::route('/{record}/edit'),
            'view' => Pages\ViewSinglePage::route('/{record}'),
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
