<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources\RoleResource\Pages;

use App\Filament\Tenant\Clusters\Settings\Resources\RoleResource;
use App\Models\Role;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('角色名称'),
                TextColumn::make('description')
                    ->label('简介'),
                IconColumn::make('is_sys')
                    ->label('系统角色'),
                TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn(Role $record) => $record->is_sys),
                RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

}
