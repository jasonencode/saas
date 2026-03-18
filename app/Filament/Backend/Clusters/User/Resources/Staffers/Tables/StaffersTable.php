<?php

namespace App\Filament\Backend\Clusters\User\Resources\Staffers\Tables;

use App\Enums\AdminType;
use App\Filament\Actions\Tenant\StafferLoginAction;
use App\Models\Tenant;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn(Builder $query) => $query->where('type', AdminType::Tenant)->latest())
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('头像'),
                Tables\Columns\TextColumn::make('name')
                    ->label('姓名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('tenants.name')
                    ->label('租户')
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('角色')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('注册时间'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('租户')
                    ->native(false)
                    ->options(fn() => Tenant::ofEnabled()->pluck('name', 'id')),
            ])
            ->recordActions([
                StafferLoginAction::make(),
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
