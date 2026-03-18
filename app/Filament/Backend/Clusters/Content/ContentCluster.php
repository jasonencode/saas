<?php

namespace App\Filament\Backend\Clusters\Content;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class ContentCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static ?string $navigationLabel = '内容管理';

    protected static ?int $navigationSort = 20;
}
