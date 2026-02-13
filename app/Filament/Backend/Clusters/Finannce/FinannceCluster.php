<?php

namespace App\Filament\Backend\Clusters\Finannce;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class FinannceCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = '财务模块';

    protected static ?int $navigationSort = 90;
}
