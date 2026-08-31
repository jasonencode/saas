<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Topics;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use App\Models\Mall\Topic;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TopicResource extends Resource
{
    protected static ?string $model = Topic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $cluster = MallCluster::class;

    protected static ?string $modelLabel = '专题';

    protected static ?string $navigationLabel = '专题管理';

    protected static ?int $navigationSort = 25;

    protected static string|UnitEnum|null $navigationGroup = '商品';

    public static function form(Schema $schema): Schema
    {
        return Schemas\TopicForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\TopicInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\TopicsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTopics::route('/'),
            'view' => Pages\ViewTopic::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductsRelationManager::class,
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
