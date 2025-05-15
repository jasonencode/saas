<?php

namespace App\Filament\Tenant\Clusters\Settings\Resources\RoleResource\Pages;

use App\Filament\Tenant\Clusters\Settings\Resources\RoleResource;
use App\Models\AdminRole;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('角色名称'),
                Tables\Columns\TextColumn::make('description')
                    ->label('简介'),
                Tables\Columns\IconColumn::make('is_sys')
                    ->label('系统角色'),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(AdminRole $record) => $record->is_sys),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
