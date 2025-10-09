<?php

namespace App\Filament\Backend\Clusters\Tenants;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class TenantsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Identification;

    protected static ?string $navigationLabel = '租户管理';

    protected static ?int $navigationSort = 3;
}
