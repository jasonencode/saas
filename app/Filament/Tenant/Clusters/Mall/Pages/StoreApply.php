<?php

namespace App\Filament\Tenant\Clusters\Mall\Pages;

use App\Filament\Tenant\Clusters\Mall\MallCluster;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class StoreApply extends Page
{
    protected static ?string $cluster = MallCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = '开店申请';

    protected static ?string $title = '开店申请';

    protected static ?int $navigationSort = -10;

    public static function canAccess(): bool
    {
        return !MallCluster::isAvailable();
    }
}