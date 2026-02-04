<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Users;

use App\Filament\Tenant\Clusters\User\Resources\Users\Pages\ManageUsers;
use App\Filament\Tenant\Clusters\User\Resources\Users\Tables\UsersTable;
use App\Filament\Tenant\Clusters\User\UserCluster;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = UserCluster::class;

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
