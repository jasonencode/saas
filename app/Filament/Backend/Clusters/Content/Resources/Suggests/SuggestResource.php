<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Suggests;

use App\Enums\Content\SuggestStatus;
use App\Filament\Backend\Clusters\Content\ContentCluster;
use App\Models\Content\Suggest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SuggestResource extends Resource
{
    protected static ?string $model = Suggest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $cluster = ContentCluster::class;

    protected static ?string $navigationLabel = '意见反馈';

    protected static ?string $modelLabel = '反馈';

    protected static ?string $pluralModelLabel = '反馈';

    protected static string|UnitEnum|null $navigationGroup = '系统';

    protected static ?int $navigationSort = 9;

    public static function getNavigationBadge(): ?string
    {
        return Suggest::where('status', SuggestStatus::Pending)->count();
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\SuggestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\SuggestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuggests::route('/'),
            'view' => Pages\ViewSuggest::route('/{record}'),
        ];
    }
}
