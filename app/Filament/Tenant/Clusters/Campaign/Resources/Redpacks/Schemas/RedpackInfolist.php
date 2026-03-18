<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Redpacks\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class RedpackInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('name')
                    ->label('活动名称'),
                Infolists\Components\TextEntry::make('start_at')
                    ->label('开始时间'),
                Infolists\Components\TextEntry::make('end_at')
                    ->label('结束时间'),
            ]);
    }
}
