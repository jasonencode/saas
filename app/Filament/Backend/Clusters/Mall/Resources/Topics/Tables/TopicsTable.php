<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Topics\Tables;

use App\Filament\Actions\Common\UpgradeSortAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class TopicsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
                Tables\Columns\ImageColumn::make('cover')
                    ->label('封面图')
                    ->square(),
                Tables\Columns\TextColumn::make('name')
                    ->label('专题名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('标识')
                    ->searchable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('商品数量')
                    ->counts('products')
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('backend.sort'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    UpgradeSortAction::make(),
                    Actions\ViewAction::make(),
                    Actions\EditAction::make(),
                    Actions\DeleteAction::make(),
                ]),
            ]);
    }
}
