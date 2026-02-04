<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Roles\Tables;

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
                Tables\Columns\TextColumn::make('name')
                    ->label('角色名称')
                    ->description(fn(AdminRole $record) => $record->description)
                    ->searchable(),
                Tables\Columns\TextColumn::make('administrators_count')
                    ->counts('administrators')
                    ->label('角色人数'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
                Tables\Filters\Filter::make('show_tenant')
                    ->label('包含租户角色')
                    ->query(fn(Builder $query): Builder => $query->whereHas('tenant')),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->hidden(fn(AdminRole $record) => $record->is_sys),
                Actions\ForceDeleteAction::make()
                    ->hidden(fn(AdminRole $record) => $record->is_sys),
                Actions\RestoreAction::make(),
            ]);
    }
}
