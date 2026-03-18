<?php

namespace App\Filament\Backend\Clusters\Campaign;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class CampaignCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::AdjustmentsHorizontal;

    protected static ?string $navigationLabel = '营销活动';

    protected static ?int $navigationSort = 40;
}
