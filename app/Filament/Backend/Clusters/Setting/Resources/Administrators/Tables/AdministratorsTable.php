<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Administrators\Tables;

use App\Models\Administrator;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdministratorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('tenants.name')
                    ->label('租户')
                    ->badge(),
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->label('角色'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('type')
                    ->label('仅后台用户')
                    ->query(fn(Builder $query): Builder => $query->whereDoesntHave('tenant')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->visible(fn(Administrator $record) => !$record->isAdministrator()),
                Actions\ForceDeleteAction::make(),
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
