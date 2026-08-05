<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\SinglePages;

use App\Filament\Tenant\Clusters\Content\ContentCluster;
use App\Filament\Tenant\Clusters\Content\Resources\SinglePages\Schemas\SinglePageForm;
use App\Filament\Tenant\Clusters\Content\Resources\SinglePages\Tables\SinglePagesTable;
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

    public static function canAccess(): bool
    {
        return ContentCluster::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return SinglePageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SinglePagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSinglePages::route('/'),
            'create' => Pages\CreateSinglePage::route('/create'),
            'edit' => Pages\EditSinglePage::route('/{record}/edit'),
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
