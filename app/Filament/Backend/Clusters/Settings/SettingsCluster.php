<?php

namespace App\Filament\Backend\Clusters\Settings;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class SettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = '系统设置';

    protected static ?int $navigationSort = 101;
}
//
//    NavigationItem::make('队列监控')
//        ->url(url: '/backend/horizon', shouldOpenInNewTab: true)
//        ->icon(Heroicon::PresentationChartLine)
//        ->group('维护')
//        ->visible(fn() => Auth::id() === 1)
//        ->sort(100),
