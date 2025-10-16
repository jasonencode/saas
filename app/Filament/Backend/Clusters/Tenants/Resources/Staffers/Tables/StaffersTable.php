<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\Staffers\Tables;

use App\Filament\Actions\Common\TenantStafferLoginAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class StaffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('头像'),
                Tables\Columns\TextColumn::make('name')
                    ->label('姓名'),
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名')
                    ->copyable(),
                Tables\Columns\TextColumn::make('tenants.name')
                    ->label('租户')
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('角色')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                TenantStafferLoginAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
