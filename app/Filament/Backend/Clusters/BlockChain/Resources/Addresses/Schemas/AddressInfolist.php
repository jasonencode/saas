<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Addresses\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class AddressInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('network.name')
                    ->label('主网')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('name')
                    ->label('地址名称'),
                Infolists\Components\TextEntry::make('address')
                    ->label('地址')
                    ->copyable()
                    ->columnSpanFull(),
                Infolists\Components\TextEntry::make('remark')
                    ->label('备注')
                    ->placeholder('-'),
            ]);
    }
}
