<?php

namespace App\Filament\Backend\Clusters\Content\Resources\Categories\Tables;

use App\Filament\Actions\Common\DisableBulkAction;
use App\Filament\Actions\Common\EnableBulkAction;
use App\Models\Category;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
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
                Tables\Filters\TrashedFilter::make(),
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
}
