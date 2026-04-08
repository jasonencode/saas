<?php

namespace App\Filament\Backend\Clusters\User\Resources\UserRelations;

use App\Filament\Backend\Clusters\User\UserCluster;
use App\Models\User\UserRelation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserRelationResource extends Resource
{
    protected static ?string $model = UserRelation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $modelLabel = '推荐';

    protected static ?string $navigationLabel = '推荐关系';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return Schemas\UserRelationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return Schemas\UserRelationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\UserRelationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUserRelations::route('/'),
            'view' => Pages\ViewUserRelation::route('/{record}'),
        ];
    }
}
