<?php

namespace App\Admin\Clusters;

use Filament\Clusters\Cluster;

class Teams extends Cluster
{
    protected static ?string $navigationLabel = '团队管理';

    protected static ?string $navigationIcon = 'heroicon-m-identification';

    protected static ?int $navigationSort = 3;
}
