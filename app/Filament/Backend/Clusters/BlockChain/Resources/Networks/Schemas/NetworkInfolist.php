<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Networks\Schemas;

use App\Models\BlockChain\Network;
use Filament\Infolists;
use Filament\Schemas\Schema;

class NetworkInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('name')
                    ->label('网络名称'),
                Infolists\Components\TextEntry::make('type')
                    ->label('主网类型')
                    ->badge(),
                Infolists\Components\TextEntry::make('rpc_url')
                    ->label('RPC地址'),
                Infolists\Components\TextEntry::make('explorer_url')
                    ->label('浏览器地址')
                    ->color('info')
                    ->url(fn (Network $network) => $network->explorer_url, true),
            ]);
    }
}
