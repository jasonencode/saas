<?php

namespace App\Filament\Tenant\Clusters\BlockChain;

use App\Enums\System\AvailableModule;
use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;

class BlockChainCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Link;

    protected static ?string $navigationLabel = '区块链';

    protected static ?int $navigationSort = 80;

    /**
     * 是否可访问
     *
     * 校验租户是否已启用区块链模块。
     */
    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return (bool) $tenant?->hasModule(AvailableModule::BlockChain);
    }
}
