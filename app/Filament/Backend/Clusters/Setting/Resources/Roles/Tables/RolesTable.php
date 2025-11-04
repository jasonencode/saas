<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\Roles\Tables;

use App\Models\AdminRole;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('角色名称')
                    ->description(fn(AdminRole $record) => $record->description)
                    ->searchable(),
                Tables\Columns\TextColumn::make('administrators_count')
                    ->counts('administrators')
                    ->label('角色人数'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
            ]);
    }
}
