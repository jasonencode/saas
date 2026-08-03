<?php

namespace App\Filament\Tenant\Clusters\Content;

use App\Enums\System\AvailableModule;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class ContentCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = '内容管理';

    protected static ?int $navigationSort = 2;

    /**
     * 是否可访问
     *
     * 校验租户是否已启用内容模块。
     */
    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return (bool) $tenant?->hasModule(AvailableModule::Content);
    }
}
