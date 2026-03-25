<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\ReturnAddresses\Tables;

use App\Filament\Actions\Mall\StoreSetDefaultReturnAddressAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class ReturnAddressesTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at')),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                StoreSetDefaultReturnAddressAction::make(),
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
