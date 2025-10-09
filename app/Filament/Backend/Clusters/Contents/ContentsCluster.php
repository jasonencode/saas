<?php

namespace App\Filament\Backend\Clusters\Contents;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class ContentsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = '内容管理';

    protected static ?int $navigationSort = 2;
}
