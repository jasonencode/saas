<?php

namespace App\Filament\Backend\Clusters\Tenants\Resources\Staffers;

use App\Filament\Actions\Common\TenantStafferLoginAction;
use App\Filament\Backend\Clusters\Tenants\Resources\Staffers\Pages\ManageStaffers;
use App\Filament\Backend\Clusters\Tenants\TenantsCluster;
use App\Models\Administrator;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class StafferResource extends Resource
{
    protected static ?string $model = Administrator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $cluster = TenantsCluster::class;

    protected static ?string $modelLabel = '租户用户';

    protected static ?string $navigationLabel = '租户用户';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
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

    public static function getPages(): array
    {
        return [
            'index' => ManageStaffers::route('/'),
        ];
    }
}
