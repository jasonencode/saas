<?php

namespace App\Filament\Backend\Clusters\Contents\Resources;

use App\Filament\Actions\Common\DisableBulkAction;
use App\Filament\Actions\Common\EnableBulkAction;
use App\Filament\Backend\Clusters\Contents;
use App\Filament\Backend\Clusters\Contents\Resources\ContentResource\Pages;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\Content;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static ?string $modelLabel = '内容';

    protected static ?string $navigationLabel = '内容管理';

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Contents::class;

    protected static bool $isScopedToTenant = false;

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
                Forms\Components\Section::make('基本信息')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 2,
                        'xl' => 2,
                        '2xl' => 3,
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('标题')
                            ->required(),
                        Forms\Components\TextInput::make('sub_title')
                            ->label('副标题'),
                        Forms\Components\Textarea::make('description')
                            ->label('简介')
                            ->rows(4),
                        Forms\Components\RichEditor::make('content')
                            ->label('内容')
                            ->required()
                            ->grow(),
                    ]),
                Forms\Components\Section::make('扩展内容')
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
                        Forms\Components\TextInput::make('author')
                            ->label('作者'),
                        Forms\Components\TextInput::make('source')
                            ->label('来源'),
                        Forms\Components\TextInput::make('views')
                            ->label('浏览量')
                            ->integer()
                            ->default(0),
                        Forms\Components\TextInput::make('sort')
                            ->label('排序')
                            ->helperText('数字越大越靠前')
                            ->integer()
                            ->default(0),
                        Forms\Components\Toggle::make('status')
                            ->translateLabel()
                            ->default(true)
                            ->inline(false)
                            ->inlineLabel(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover')
                    ->label('封面图'),
                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->description(fn(Content $record) => $record->sub_title)
                    ->searchable(),
                Tables\Columns\TextColumn::make('categories.name')
                    ->label('分类')
                    ->badge(),
                Tables\Columns\TextColumn::make('views')
                    ->label('浏览量'),
                Tables\Columns\IconColumn::make('status')
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('sort')
                    ->label('排序'),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    EnableBulkAction::make(),
                    DisableBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContents::route('/'),
            'create' => Pages\CreateContent::route('/create'),
            'edit' => Pages\EditContent::route('/{record}/edit'),
        ];
    }
}
