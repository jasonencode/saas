<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\Categories\Tables;

use App\Enums\Content\CategoryType;
use App\Filament\Actions\Common\UpgradeSortAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Kisame76\FilamentTreeTable\ExpandableRows;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        $table = ExpandableRows::make()
            ->defaultSort('sort', 'desc')
            ->expandAllAction(false)
            ->collapseAllAction(false)
            ->paginateByRoot()
            ->flattenOnFilter(false)
            ->flattenOnSearch(false)
            ->applyTo(
                $table
                    ->searchPlaceholder('分类名称')
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('type', CategoryType::Content))
                    ->columns([
                        Tables\Columns\ImageColumn::make('cover')
                            ->square()
                            ->label('图标'),
                        Tables\Columns\TextColumn::make('name')
                            ->label('分类名称')
                            ->searchable(),
                        Tables\Columns\IconColumn::make('status')
                            ->label(__('backend.status')),
                        Tables\Columns\TextColumn::make('sort')
                            ->label(__('backend.sort'))
                            ->sortable(),
                        Tables\Columns\TextColumn::make('created_at')
                            ->label(__('backend.created_at'))
                            ->dateTime()
                            ->sortable(),
                    ])
                    ->recordActions([
                        Actions\ActionGroup::make([
                            UpgradeSortAction::make(),
                            Actions\EditAction::make(),
                            Actions\DeleteAction::make(),
                        ]),
                    ])
            );

        $table->getColumn(ExpandableRows::TOGGLE_COLUMN_NAME)?->disabledClick();

        return $table;
    }
}
