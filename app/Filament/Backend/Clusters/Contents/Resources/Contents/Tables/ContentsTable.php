<?php

namespace App\Filament\Backend\Clusters\Contents\Resources\Contents\Tables;

use App\Filament\Actions\Common\DisableBulkAction;
use App\Filament\Actions\Common\EnableBulkAction;
use App\Models\Content;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class ContentsTable
{
    public static function configure(Table $table): Table
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
}
