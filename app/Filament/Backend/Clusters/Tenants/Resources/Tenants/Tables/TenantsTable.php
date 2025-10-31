<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\Tenants\Tables;

use App\Filament\Actions\Tenant\TenantRenewalAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('LOGO')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('租户名称')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('简称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('administrators_count')
                    ->counts('administrators')
                    ->label('人员'),
                Tables\Columns\TextColumn::make('roles_count')
                    ->counts('roles')
                    ->label('角色'),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态'),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('到期时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                TenantRenewalAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
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
