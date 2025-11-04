<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Categories\Schemas;

use App\Filament\Forms\Components\CustomUpload;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('分类名称')
                    ->required(),
                SelectTree::make('parent_id')
                    ->label('上级分类')
                    ->relationship(
                        relationship: 'parent',
                        titleAttribute: 'name',
                        parentAttribute: 'parent_id',
                        modifyQueryUsing: fn(Builder $query) => $query->disableCache(),
                        modifyChildQueryUsing: fn(Builder $query) => $query->disableCache(),
                    )
                    ->defaultOpenLevel(2)
                    ->withCount()
                    ->enableBranchNode()
                    ->searchable(),
                Forms\Components\Textarea::make('description')
                    ->label('简介')
                    ->rows(4),
                CustomUpload::make()
                    ->label('封面图'),
                Forms\Components\Toggle::make('status')
                    ->label('状态')
                    ->default(true),
                Forms\Components\TextInput::make('sort')
                    ->label('排序')
                    ->integer()
                    ->default(0),
            ]);

    }
}
