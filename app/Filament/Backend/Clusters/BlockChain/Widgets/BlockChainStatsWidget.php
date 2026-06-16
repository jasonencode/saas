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
use Illuminate\Support\Facades\Cache;

class BlockChainStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $cacheKey = 'blockchain_stats_widget';
        $cacheTtl = 60;

        $data = Cache::remember($cacheKey, $cacheTtl, static function () {
            return [
                'total_networks' => Network::count(),
                'active_networks' => Network::where('status', true)->count(),
                'fisco' => Network::where('type', 'fisco')->count(),
                'chain33' => Network::where('type', 'chain33')->count(),
                'total_addresses' => ChainAddress::count(),
                'total_contracts' => Contract::count(),
                'total_repositories' => ContractRepository::count(),
                'total_certs' => Certificate::count(),
                'active_certs' => Certificate::where('status', true)->count(),
                'ca_certs' => Certificate::where('type', 'ca')->count(),
                'intermediate_certs' => Certificate::where('type', 'intermediate')->count(),
                'client_certs' => Certificate::where('type', 'certificate')->count(),
            ];
        });

        return [
            Stat::make('区块链网络', $data['total_networks'])
                ->description("启用：{$data['active_networks']} / 停用：".($data['total_networks'] - $data['active_networks']))
                ->descriptionIcon(Heroicon::OutlinedServerStack)
                ->color('primary')
                ->url(NetworkResource::getUrl()),

            Stat::make('网络类型分布', "FISCO {$data['fisco']} · Chain33 {$data['chain33']}")
                ->description('底层区块链网络结构统计')
                ->descriptionIcon(Heroicon::OutlinedSquares2x2)
                ->color('info')
                ->url(NetworkResource::getUrl()),

            Stat::make('链地址', $data['total_addresses'])
                ->description('所有区块链网络地址总数')
                ->descriptionIcon(Heroicon::OutlinedIdentification)
                ->color('success')
                ->url(AddressResource::getUrl()),

            Stat::make('智能合约', $data['total_contracts'])
                ->description('已部署的智能合约总数')
                ->descriptionIcon(Heroicon::OutlinedCodeBracket)
                ->color('warning')
                ->url(ContractResource::getUrl()),

            Stat::make('合约仓库', $data['total_repositories'])
                ->description('保存 .sol 源码的仓库记录数')
                ->descriptionIcon(Heroicon::OutlinedArchiveBox)
                ->color('gray')
                ->url(ContractRepositoryResource::getUrl()),

            Stat::make('证书总数', $data['total_certs'])
                ->description("启用：{$data['active_certs']} / 停用：".($data['total_certs'] - $data['active_certs']))
                ->descriptionIcon(Heroicon::OutlinedShieldCheck)
                ->color('info')
                ->url(CertificateResource::getUrl()),

            Stat::make('证书类型', "CA {$data['ca_certs']} · 中间 {$data['intermediate_certs']} · 客户端 {$data['client_certs']}")
                ->description('CA / Intermediate / Certificate 分布')
                ->descriptionIcon(Heroicon::OutlinedKey)
                ->color('gray')
                ->url(CertificateResource::getUrl()),
        ];
    }
}
