<?php

namespace App\Filament\Backend\Clusters\User;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class UserCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = '用户管理';

    protected static ?int $navigationSort = 10;
}
