<?php

namespace App\Filament\Tenant\Clusters\Setting;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class SettingCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static ?string $navigationLabel = '系统设置';

    protected static ?int $navigationSort = 101;
}
