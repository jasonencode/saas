<?php

namespace App\Filament\Tenant\Clusters\Finance;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class FinanceCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::CursorArrowRipple;

    protected static ?string $navigationLabel = '财务模块';

    protected static ?int $navigationSort = 90;
}
