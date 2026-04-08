<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Categories\Schemas;

use App\Enums\Content\CategoryType;
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
                Forms\Components\Hidden::make('type')
                    ->default(CategoryType::Product),
                Forms\Components\TextInput::make('name')
                    ->label('分类名称')
                    ->required(),
                SelectTree::make('parent_id')
                    ->label('上级分类')
                    ->relationship(
                        relationship: 'parent',
                        titleAttribute: 'name',
                        parentAttribute: 'parent_id',
                        modifyQueryUsing: fn (Builder $query) => $query->where('type', CategoryType::Product)->ofEnabled(),
                        modifyChildQueryUsing: fn (Builder $query) => $query->where('type', CategoryType::Product)->ofEnabled(),
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
                    ->label(__('backend.status')),
                Forms\Components\TextInput::make('sort')
                    ->label(__('backend.sort'))
                    ->required()
                    ->integer()
                    ->default(0)
                    ->helperText('数字越大越靠前'),
            ]);
    }
}

