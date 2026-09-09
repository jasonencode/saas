<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Redpacks\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class RedpackInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('基本信息')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label(__('backend.tenant'))
                            ->badge(),
                        Infolists\Components\TextEntry::make('name')
                            ->label('活动名称'),
                        Infolists\Components\TextEntry::make('start_at')
                            ->label('开始时间')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('end_at')
                            ->label('结束时间')
                            ->dateTime(),
                    ]),
            ]);
    }
}
