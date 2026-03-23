<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\ReturnAddresses\Tables;

use App\Filament\Actions\Mall\SetDefaultReturnAddressAction;
use App\Filament\Tables\Filters\TenantFilter;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class ReturnAddressesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
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
                TenantFilter::make(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                SetDefaultReturnAddressAction::make(),
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
