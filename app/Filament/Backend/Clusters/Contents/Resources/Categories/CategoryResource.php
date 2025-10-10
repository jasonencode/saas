<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\Categories;

use App\Filament\Actions\Common\DisableBulkAction;
use App\Filament\Actions\Common\EnableBulkAction;
use App\Filament\Backend\Clusters\Contents\ContentsCluster;
use App\Filament\Forms\Components\CustomUpload;
use App\Models\Category;
use BackedEnum;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $cluster = ContentsCluster::class;

    protected static ?string $modelLabel = '分类';

    protected static ?string $navigationLabel = '分类管理';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
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
                CustomUpload::make('cover')
                    ->label('封面图'),
                Forms\Components\Toggle::make('status')
                    ->label('状态')
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
                    ->label('状态'),
                Tables\Columns\TextColumn::make('sort')
                    ->label('排序'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\RestoreAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    EnableBulkAction::make(),
                    DisableBulkAction::make(),
                    Actions\DeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
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
