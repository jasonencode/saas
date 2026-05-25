<?php

namespace App\Filament\Tenant\Clusters\BlockChain\Resources\Networks\Schemas;

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
                Infolists\Components\TextEntry::make('group_id')
                    ->label('群组 ID')
                    ->visible(fn (Network $network) => $network->group_id !== null),
                Infolists\Components\TextEntry::make('explorer_url')
                    ->label('浏览器地址')
                    ->color('info')
                    ->url(fn (Network $network) => $network->explorer_url, true),
                Infolists\Components\IconEntry::make('ca_cert')
                    ->label('CA 证书')
                    ->boolean(),
                Infolists\Components\IconEntry::make('client_cert')
                    ->label('客户端证书')
                    ->boolean(),
                Infolists\Components\IconEntry::make('client_key')
                    ->label('客户端私钥')
                    ->boolean(),
            ]);
    }
}
