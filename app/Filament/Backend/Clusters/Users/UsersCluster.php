<?php

namespace App\Filament\Backend\Clusters\Users;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class UsersCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 1;
}
