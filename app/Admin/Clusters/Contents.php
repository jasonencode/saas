<?php

namespace App\Admin\Clusters;

use Filament\Clusters\Cluster;

class Contents extends Cluster
{
    protected static ?string $navigationLabel = '内容管理';

    protected static ?string $navigationIcon = 'heroicon-c-rectangle-group';

    protected static ?int $navigationSort = 2;
}
