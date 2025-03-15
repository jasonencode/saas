<?php

namespace App\Filament\Backend\Clusters\Contents\Resources;

use App\Filament\Actions\DisableBulkAction;
use App\Filament\Actions\EnableBulkAction;
use App\Filament\Backend\Clusters\Contents;
use App\Filament\Backend\Clusters\Contents\Resources\CategoryResource\Pages;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\Category;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $modelLabel = '分类';

    protected static ?string $navigationLabel = '分类管理';

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Contents::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                CustomUpload::make('cover')
                    ->label('封面图'),
                Forms\Components\Toggle::make('status')
                    ->translateLabel()
                    ->default(true)
                    ->inline(false)
                    ->inlineLabel(false),
                Forms\Components\TextInput::make('sort')
                    ->label('排序')
                    ->integer()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('分类名称')
                    ->searchable()
                    ->description(fn(Category $record) => $record->description),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('上级分类'),
                Tables\Columns\IconColumn::make('status')
                    ->translateLabel(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
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
            'index' => Pages\ManageCategories::route('/'),
        ];
    }
}
