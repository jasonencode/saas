<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Applies\Tables;

use App\Filament\Actions\Mall\StoreApplyAuditAction;
use App\Filament\Tables\Filters\TenantFilter;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class AppliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->searchable()
                    ->badge(),
                Tables\Columns\TextColumn::make('store_name')
                    ->label('店铺名称'),
                Tables\Columns\TextColumn::make('contactor')
                    ->label('联系人'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('联系电话'),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge(),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('审核员'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at')),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                StoreApplyAuditAction::make(),
                Actions\ViewAction::make(),
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
