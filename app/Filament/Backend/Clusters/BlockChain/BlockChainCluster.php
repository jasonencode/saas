<?php

namespace App\Filament\Backend\Clusters\BlockChain;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class BlockChainCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = '区块链';

    protected static ?int $navigationSort = 80;
}
