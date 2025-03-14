<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\AdminRoleResource\Pages;

use App\Filament\Backend\Clusters\Settings\Resources\AdminRoleResource;
use App\Models\AdminRole;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
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
                TextColumn::make('name')
                    ->label('角色名称')
                    ->description(fn(AdminRole $record) => $record->description),
                TextColumn::make('administrators_count')
                    ->counts('administrators')
                    ->label('角色人数'),
                TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
