<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RedpackInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('活动名称'),
                TextEntry::make('tenant.name')
                    ->label('租户名称'),
                TextEntry::make('start_at')
                    ->label('开始时间'),
                TextEntry::make('end_at')
                    ->label('结束时间'),
            ]);
    }
}
