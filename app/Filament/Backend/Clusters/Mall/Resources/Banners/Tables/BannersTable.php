<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Banners\Tables;

use App\Filament\Actions\Common\UpgradeSortAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn(Builder $query) => $query->bySort())
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('租户')
                    ->visible(isBackend())
                    ->searchable(),
                Tables\Columns\ImageColumn::make('cover')
                    ->label('图片'),
                Tables\Columns\TextColumn::make('title')
                    ->label('横幅标题'),
                Tables\Columns\TextColumn::make('jump')
                    ->label('跳转链接'),
                Tables\Columns\TextColumn::make('sort')
                    ->label('排序'),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                UpgradeSortAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}

