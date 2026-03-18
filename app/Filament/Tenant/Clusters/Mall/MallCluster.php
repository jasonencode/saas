<?php

namespace App\Filament\Tenant\Clusters\Mall;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class MallCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingBag;

    protected static ?string $navigationLabel = '商城模块';

    protected static ?int $navigationSort = 3;
}
