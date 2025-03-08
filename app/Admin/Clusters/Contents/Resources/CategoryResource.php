<?php

namespace App\Admin\Clusters\Contents\Resources;

use App\Admin\Actions\BulkDisableAction;
use App\Admin\Actions\BulkEnableAction;
use App\Admin\Clusters\Contents;
use App\Admin\Clusters\Contents\Resources\CategoryResource\Pages\ManageCategories;
use App\Admin\Forms\Components\CustomUpload;
use App\Models\Category;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
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
                TextInput::make('name')
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
                Textarea::make('description')
                    ->label('简介')
                    ->rows(4),
                CustomUpload::make('cover')
                    ->label('封面图'),
                Toggle::make('status')
                    ->translateLabel()
                    ->default(true),
                TextInput::make('sort')
                    ->label('排序')
                    ->integer()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('分类名称')
                    ->searchable()
                    ->description(fn(Category $record) => $record->description),
                TextColumn::make('parent.name')
                    ->label('上级分类'),
                IconColumn::make('status')
                    ->translateLabel(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkEnableAction::make(),
                    BulkDisableAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
