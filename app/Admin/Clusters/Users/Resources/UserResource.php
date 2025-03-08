<?php

namespace App\Admin\Clusters\Users\Resources;

use App\Admin\Clusters\Users;
use App\Admin\Clusters\Users\Resources\UserResource\Pages\ManageUsers;
use App\Models\User;
use Filament\Resources\Resource;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = '用户';

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Users::class;

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
