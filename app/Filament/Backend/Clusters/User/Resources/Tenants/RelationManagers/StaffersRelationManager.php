<?php

namespace App\Filament\Backend\Clusters\User\Resources\Tenants\RelationManagers;

use App\Enums\AdminType;
use App\Filament\Backend\Clusters\Setting\Resources\Administrators\Schemas\AdministratorForm;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StaffersRelationManager extends RelationManager
{
    protected static string $relationship = 'administrators';

    protected static ?string $modelLabel = '员工';

    protected static ?string $title = '租户成员';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return AdministratorForm::configure($schema, AdminType::Tenant);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function(Builder $query): Builder {
                return $query->latest('administrators.created_at');
            })
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('头像')
                    ->circular(),
                Tables\Columns\TextColumn::make('username')
                    ->label('用户名')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('昵称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->label('角色'),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }
}
