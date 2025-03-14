<?php

namespace App\Filament\Backend\Clusters;

use Filament\Clusters\Cluster;

class Tenants extends Cluster
{
    protected static ?string $navigationLabel = '租户管理';

    protected static ?string $navigationIcon = 'heroicon-m-identification';

    protected static ?int $navigationSort = 3;
}
