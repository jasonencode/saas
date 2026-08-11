<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Roles\Tables;

use App\Models\System\AdminRole;
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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('tenant_id'))
            ->columns([
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\ActionGroup::make([
                    Actions\EditAction::make(),
                    Actions\DeleteAction::make()
                        ->hidden(fn (AdminRole $record) => $record->is_sys),
                    Actions\RestoreAction::make(),
                    Actions\ForceDeleteAction::make()
                        ->hidden(fn (AdminRole $record) => $record->is_sys),
                ]),
            ]);
    }
}
