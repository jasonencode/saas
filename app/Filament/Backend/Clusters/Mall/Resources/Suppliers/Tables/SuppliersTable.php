<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Suppliers\Tables;

use App\Filament\Tables\Filters\TenantFilter;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn(Builder $query) => $query->bySort())
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
                Tables\Columns\ImageColumn::make('cover')
                    ->label('图片')
                    ->imageSize(60),
                Tables\Columns\TextColumn::make('name')
                    ->label('供应商名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sort')
                    ->label('排序')
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at')),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\RestoreAction::make(),
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
