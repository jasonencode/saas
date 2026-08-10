<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Lotteries\Tables;

use App\Filament\Tables\Filters\TenantFilter;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class LotteriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant')),
                Tables\Columns\TextColumn::make('name')
                    ->label('活动名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('draw_mode')
                    ->label('抽奖模式')
                    ->badge(),
                Tables\Columns\TextColumn::make('start_at')
                    ->label('开始时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_at')
                    ->label('结束时间')
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
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
