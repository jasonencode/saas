<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Contents\Schemas;

use App\Enums\CategoryType;
use App\Filament\Forms\Components\CustomUpload;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 3,
                '2xl' => 4,
            ])
            ->components([
                Schemas\Components\Section::make('基本信息')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 2,
                        'xl' => 2,
                        '2xl' => 3,
                    ])
                    ->components([
                        Forms\Components\TextInput::make('title')
                            ->label('标题')
                            ->required(),
                        Forms\Components\TextInput::make('sub_title')
                            ->label('副标题'),
                        Forms\Components\Textarea::make('description')
                            ->label('简介')
                            ->rows(4),
                        Forms\Components\RichEditor::make('content')
                            ->resizableImages()
                            ->label('内容')
                            ->required()
                            ->grow(),
                    ]),
                Schemas\Components\Section::make('扩展内容')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 1,
                        'xl' => 1,
                        '2xl' => 1,
                    ])
                    ->components([
                        SelectTree::make('categories')
                            ->label('分类')
                            ->relationship(
                                relationship: 'categories',
                                titleAttribute: 'name',
                                parentAttribute: 'parent_id',
                                modifyQueryUsing: fn (Builder $query) => $query->where('type', CategoryType::Content)->ofEnabled(),
                                modifyChildQueryUsing: fn (Builder $query) => $query->where('type', CategoryType::Content)->ofEnabled(),
                            )
                            ->dehydrated(false)
                            ->defaultOpenLevel(2)
                            ->enableBranchNode()
                            ->required()
                            ->searchable()
                            ->withCount()
                            ->default([]),
                        CustomUpload::cover(),
                        Forms\Components\TextInput::make('author')
                            ->label('作者'),
                        Forms\Components\TextInput::make('source')
                            ->label('来源'),
                        Forms\Components\TextInput::make('views')
                            ->label('浏览量')
                            ->integer()
                            ->default(0),
                        Forms\Components\TextInput::make('sort')
                            ->label(__('backend.sort'))
                            ->required()
                            ->helperText('数字越大越靠前')
                            ->integer()
                            ->default(0),
                        Forms\Components\Toggle::make('status')
                            ->label(__('backend.status')),
                    ]),
            ]);
    }
}
