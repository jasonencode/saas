<?php

namespace App\Filament\Backend\Clusters\Contents\Resources;

use App\Filament\Backend\Clusters\Contents;
use App\Filament\Backend\Clusters\Contents\Resources\ContentResource\Pages\CreateContent;
use App\Filament\Backend\Clusters\Contents\Resources\ContentResource\Pages\EditContent;
use App\Filament\Backend\Clusters\Contents\Resources\ContentResource\Pages\ListContents;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\Content;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static ?string $modelLabel = '内容';

    protected static ?string $navigationLabel = '内容管理';

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Contents::class;

    public static function form(Form $form): Form
    {
        return $form
            ->columns([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 3,
                '2xl' => 4,
            ])
            ->schema([
                Section::make('基本信息')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 2,
                        'xl' => 2,
                        '2xl' => 3,
                    ])
                    ->schema([
                        TextInput::make('title')
                            ->label('标题')
                            ->required(),
                        TextInput::make('sub_title')
                            ->label('副标题'),
                        Textarea::make('description')
                            ->label('简介')
                            ->rows(4),
                        RichEditor::make('content')
                            ->label('内容')
                            ->required()
                            ->grow(),
                    ]),
                Section::make('扩展内容')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 1,
                        'xl' => 1,
                        '2xl' => 1,
                    ])
                    ->schema([
                        SelectTree::make('categories')
                            ->label('分类')
                            ->relationship(
                                relationship: 'categories',
                                titleAttribute: 'name',
                                parentAttribute: 'parent_id',
                                modifyQueryUsing: fn(Builder $query) => $query->ofEnabled()->disableCache(),
                                modifyChildQueryUsing: fn(Builder $query) => $query->ofEnabled()->disableCache(),
                            )
                            ->dehydrated(false)
                            ->defaultOpenLevel(2)
                            ->enableBranchNode()
                            ->required()
                            ->searchable()
                            ->withCount(),
                        CustomUpload::make('cover')
                            ->label('封面图'),
                        TextInput::make('author')
                            ->label('作者'),
                        TextInput::make('source')
                            ->label('来源'),
                        TextInput::make('views')
                            ->label('浏览量')
                            ->integer()
                            ->default(0),
                        TextInput::make('sort')
                            ->label('排序')
                            ->helperText('数字越大越靠前')
                            ->integer()
                            ->default(0),
                        Toggle::make('status')
                            ->translateLabel()
                            ->default(true)
                            ->inline(false)
                            ->inlineLabel(false),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContents::route('/'),
            'create' => CreateContent::route('/create'),
            'edit' => EditContent::route('/{record}/edit'),
        ];
    }
}
