<?php

namespace App\Filament\Backend\Clusters\BlockChain\Widgets;

use App\Filament\Backend\Clusters\BlockChain\Resources\Addresses\AddressResource;
use App\Filament\Backend\Clusters\BlockChain\Resources\Certificates\CertificateResource;
use App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories\ContractRepositoryResource;
use App\Filament\Backend\Clusters\BlockChain\Resources\Contracts\ContractResource;
use App\Filament\Backend\Clusters\BlockChain\Resources\Networks\NetworkResource;
use App\Models\BlockChain\Certificate;
use App\Models\BlockChain\ChainAddress;
use App\Models\BlockChain\Contract;
use App\Models\BlockChain\ContractRepository;
use App\Models\BlockChain\Network;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BlockChainStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalNetworks = Network::count();
        $activeNetworks = Network::where('status', true)->count();

        $fisco = Network::where('type', 'fisco')->count();
        $chain33 = Network::where('type', 'chain33')->count();

        $totalAddresses = ChainAddress::count();
        $totalContracts = Contract::count();
        $totalRepositories = ContractRepository::count();
        $totalCerts = Certificate::count();
        $activeCerts = Certificate::where('status', true)->count();

        $caCerts = Certificate::where('type', 'ca')->count();
        $intermediateCerts = Certificate::where('type', 'intermediate')->count();
        $clientCerts = Certificate::where('type', 'certificate')->count();

        return [
            Stat::make('区块链网络', $totalNetworks)
                ->description('启用：'.$activeNetworks.' / 停用：'.($totalNetworks - $activeNetworks))
                ->descriptionIcon(Heroicon::OutlinedServerStack)
                ->color('primary')
                ->url(NetworkResource::getUrl()),

            Stat::make('网络类型分布', 'FISCO '.$fisco.' · Chain33 '.$chain33)
                ->description('底层区块链网络结构统计')
                ->descriptionIcon(Heroicon::OutlinedSquares2x2)
                ->color('info')
                ->url(NetworkResource::getUrl()),

            Stat::make('链地址', $totalAddresses)
                ->description('所有区块链网络地址总数')
                ->descriptionIcon(Heroicon::OutlinedIdentification)
                ->color('success')
                ->url(AddressResource::getUrl()),

            Stat::make('智能合约', $totalContracts)
                ->description('已部署的智能合约总数')
                ->descriptionIcon(Heroicon::OutlinedCodeBracket)
                ->color('warning')
                ->url(ContractResource::getUrl()),

            Stat::make('合约仓库', $totalRepositories)
                ->description('保存 .sol 源码的仓库记录数')
                ->descriptionIcon(Heroicon::OutlinedArchiveBox)
                ->color('gray')
                ->url(ContractRepositoryResource::getUrl()),

            Stat::make('证书总数', $totalCerts)
                ->description('启用：'.$activeCerts.' / 停用：'.($totalCerts - $activeCerts))
                ->descriptionIcon(Heroicon::OutlinedShieldCheck)
                ->color('info')
                ->url(CertificateResource::getUrl()),

            Stat::make('证书类型', 'CA '.$caCerts.' · 中间 '.$intermediateCerts.' · 客户端 '.$clientCerts)
                ->description('CA / Intermediate / Certificate 分布')
                ->descriptionIcon(Heroicon::OutlinedKey)
                ->color('gray')
                ->url(CertificateResource::getUrl()),
        ];
    }
}
