<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\AdminRoleResource\Pages;

use App\Filament\Backend\Clusters\Settings\Resources\AdminRoleResource;
use App\Models\AdminRole;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManageRoles extends ManageRecords
{
    protected static string $resource = AdminRoleResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function(Builder $query): Builder {
                return $query->whereNull('tenant_id')->latest();
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('角色名称')
                    ->description(fn(AdminRole $record) => $record->description),
                Tables\Columns\TextColumn::make('administrators_count')
                    ->counts('administrators')
                    ->label('角色人数'),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
