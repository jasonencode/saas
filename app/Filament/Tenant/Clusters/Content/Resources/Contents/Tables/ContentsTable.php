<?php

namespace App\Filament\Tenant\Clusters\Content\Resources\Contents\Tables;

use App\Filament\Actions\Common\DisableBulkAction;
use App\Filament\Actions\Common\EnableBulkAction;
use App\Filament\Actions\Common\UpgradeViewsAction;
use App\Models\Content\Content;
use Filament\Actions;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use Illuminate\Database\Eloquent\Builder;

class ContentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->bySort())
            ->columns([
                Tables\Columns\ImageColumn::make('cover')
                    ->label('封面图')
                    ->action(
                        MediaAction::make('cover')
                            ->label('封面预览')
                            ->modalWidth(Width::Large)
                            ->visible(fn (Content $record) => $record->cover)
                            ->media(fn (Content $record) => $record->cover_url),
                    ),
                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->description(fn (Content $record) => $record->sub_title)
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('分类')
                    ->badge()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('views')
                    ->label('浏览量'),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status')),
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('backend.sort'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('分类')
                    ->relationship(
                        'category',
                        'name'
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    UpgradeViewsAction::make(),
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
            ]);
    }
}
