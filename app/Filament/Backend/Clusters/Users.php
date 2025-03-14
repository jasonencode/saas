<?php

namespace App\Filament\Backend\Clusters;

use Filament\Clusters\Cluster;

class Users extends Cluster
{
    protected static ?string $navigationLabel = '用户管理';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 1;
}
