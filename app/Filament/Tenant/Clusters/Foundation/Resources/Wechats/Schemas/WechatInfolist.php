<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Wechats\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class WechatInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                Infolists\Components\TextEntry::make('name')
                    ->label('微信名称'),
                Infolists\Components\TextEntry::make('app_id')
                    ->label('AppId'),
                Infolists\Components\IconEntry::make('status')
                    ->label('状态'),
                Infolists\Components\IconEntry::make('is_connected')
                    ->label('连接状态'),
            ]);
    }
}
