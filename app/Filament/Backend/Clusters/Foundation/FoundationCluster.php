<?php

namespace App\Filament\Backend\Clusters\Foundation;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class FoundationCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::CubeTransparent;

    protected static ?string $navigationLabel = '基础设施';

    protected static ?int $navigationSort = 80;
}
