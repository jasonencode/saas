<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Roles\Tables;

use App\Filament\Tables\Filters\TenantFilter;
use App\Models\AdminRole;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
                Tables\Columns\TextColumn::make('name')
                    ->label('角色名称')
                    ->description(fn (AdminRole $record) => $record->description)
                    ->searchable(),
                Tables\Columns\TextColumn::make('administrators_count')
                    ->counts('administrators')
                    ->label('角色人数'),
                Tables\Columns\IconColumn::make('is_sys')
                    ->label('系统角色'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\Filter::make('show_tenant')
                    ->label('仅后台角色')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('tenant')),
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->hidden(fn (AdminRole $record) => $record->is_sys),
                Actions\ForceDeleteAction::make()
                    ->hidden(fn (AdminRole $record) => $record->is_sys),
                Actions\RestoreAction::make(),
            ]);
    }
}
