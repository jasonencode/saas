<?php

namespace App\Admin\Clusters\Contents\Resources\ContentResource\Pages;

use App\Admin\Actions\BulkDisableAction;
use App\Admin\Actions\BulkEnableAction;
use App\Admin\Clusters\Contents\Resources\ContentResource;
use App\Models\Content;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListContents extends ListRecords
{
    protected static string $resource = ContentResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover')
                    ->label('封面图'),
                TextColumn::make('title')
                    ->label('标题')
                    ->description(fn(Content $record) => $record->sub_title)
                    ->searchable(),
                TextColumn::make('categories.name')
                    ->label('分类')
                    ->badge(),
                TextColumn::make('views')
                    ->label('浏览量'),
                IconColumn::make('status')
                    ->translateLabel(),
                TextColumn::make('sort')
                    ->label('排序'),
                TextColumn::make('created_at')
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
}
