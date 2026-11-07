<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Categories\Tables;

use App\Enums\Content\CategoryType;
use App\Filament\Actions\Common\DisableBulkAction;
use App\Filament\Actions\Common\EnableBulkAction;
use App\Filament\Actions\Common\UpgradeSortAction;
use App\Filament\Tables\Filters\TenantFilter;
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
                    ->modifyQueryUsing(fn (Builder $query) => $query->where('type', CategoryType::Product))
                    ->columns([
                        Tables\Columns\ImageColumn::make('cover')
                            ->square()
                            ->label('图标'),
                        Tables\Columns\TextColumn::make('tenant.name')
                            ->label(__('backend.tenant'))
                            ->badge(),
                        Tables\Columns\TextColumn::make('name')
                            ->label('分类名称')
                            ->searchable(),
                        Tables\Columns\IconColumn::make('status')
                            ->label(__('backend.status')),
                        Tables\Columns\IconColumn::make('is_home')
                            ->label('首页展示'),
                        Tables\Columns\TextColumn::make('sort')
                            ->label(__('backend.sort'))
                            ->sortable(),
                        Tables\Columns\TextColumn::make('created_at')
                            ->label(__('backend.created_at'))
                            ->dateTime()
                            ->sortable(),
                    ])
                    ->filters([
                        TenantFilter::make(),
                        Tables\Filters\TrashedFilter::make(),
                    ])
                    ->recordActions([
                        Actions\ActionGroup::make([
                            UpgradeSortAction::make(),
                            Actions\EditAction::make(),
                            Actions\DeleteAction::make(),
                            Actions\RestoreAction::make(),
                        ]),
                    ])
                    ->toolbarActions([
                        Actions\BulkActionGroup::make([
                            EnableBulkAction::make(),
                            DisableBulkAction::make(),
                            Actions\DeleteBulkAction::make(),
                            Actions\RestoreBulkAction::make(),
                        ]),
                    ])
            );

        $table->getColumn(ExpandableRows::TOGGLE_COLUMN_NAME)?->disabledClick();

        return $table;
    }
}
