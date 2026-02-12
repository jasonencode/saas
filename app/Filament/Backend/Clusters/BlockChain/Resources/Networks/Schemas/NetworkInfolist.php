<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Networks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class NetworkInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('区块链网络名称'),
            ]);
    }
}
