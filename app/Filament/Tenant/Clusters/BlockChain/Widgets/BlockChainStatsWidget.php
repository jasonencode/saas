<?php

namespace App\Filament\Tenant\Clusters\BlockChain\Widgets;

use App\Filament\Tenant\Clusters\BlockChain\Resources\Addresses\AddressResource;
use App\Filament\Tenant\Clusters\BlockChain\Resources\Certificates\CertificateResource;
use App\Filament\Tenant\Clusters\BlockChain\Resources\Contracts\ContractResource;
use App\Filament\Tenant\Clusters\BlockChain\Resources\Networks\NetworkResource;
use App\Models\BlockChain\Certificate;
use App\Models\BlockChain\ChainAddress;
use App\Models\BlockChain\Contract;
use App\Models\BlockChain\Network;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BlockChainStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('网络总数', Network::count())
                ->description('所有区块链网络')
                ->descriptionIcon(Heroicon::OutlinedServerStack)
                ->color('primary')
                ->url(NetworkResource::getIndexUrl()),

            Stat::make('地址总数', ChainAddress::count())
                ->description('所有区块链地址')
                ->descriptionIcon(Heroicon::OutlinedWallet)
                ->color('success')
                ->url(AddressResource::getIndexUrl()),

            Stat::make('合约总数', Contract::count())
                ->description('已部署的智能合约')
                ->descriptionIcon(Heroicon::OutlinedCodeBracket)
                ->color('warning')
                ->url(ContractResource::getIndexUrl()),

            Stat::make('证书总数', Certificate::count())
                ->description('所有区块链证书')
                ->descriptionIcon(Heroicon::OutlinedShieldCheck)
                ->color('info')
                ->url(CertificateResource::getIndexUrl()),
        ];
    }
}
