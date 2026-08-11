<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\ReturnAddresses\Tables;

use App\Filament\Actions\Common\SetDefaultAction;
use App\Filament\Actions\Common\UpgradeSortAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReturnAddressesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderByDesc('is_default')->bySort())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('联系电话')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_address')
                    ->label('完整地址')
                    ->copyable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('默认地址'),
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('backend.sort')),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    SetDefaultAction::make(),
                    UpgradeSortAction::make(),
                    Actions\EditAction::make(),
                    Actions\DeleteAction::make(),
                ]),
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
