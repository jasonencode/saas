<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Comments;

use App\Filament\Backend\Clusters\Content\ContentCluster;
use App\Models\Comment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
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
}
