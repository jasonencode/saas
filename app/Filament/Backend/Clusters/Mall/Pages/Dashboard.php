<?php

namespace App\Filament\Backend\Clusters\Mall\Pages;

use App\Filament\Backend\Clusters\Mall\MallCluster;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class Dashboard extends Page
{
    protected static ?string $cluster = MallCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = '数据看板';

    protected static ?string $title = '数据看板';

    protected static ?int $navigationSort = -2;
}

