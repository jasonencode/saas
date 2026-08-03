<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\Comments;

use App\Filament\Tenant\Clusters\Content\ContentCluster;
use App\Models\Content\Comment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?string $cluster = ContentCluster::class;

    protected static ?string $modelLabel = '评论';

    protected static ?string $navigationLabel = '评论管理';

    protected static ?int $navigationSort = 3;

    protected static string|UnitEnum|null $navigationGroup = '内容';

    public static function canAccess(): bool
    {
        return ContentCluster::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return Schemas\CommentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\CommentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageComments::route('/'),
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
