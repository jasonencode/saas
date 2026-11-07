<?php

namespace App\Filament\Infolists\Campaign;

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
                    ->columnSpanFull()
                    ->columns(filament()->getTenant() === null ? 5 : 4)
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label(__('backend.tenant'))
                            ->badge()
                            ->visible(fn (): bool => filament()->getTenant() === null),
                        Infolists\Components\TextEntry::make('name')
                            ->label('活动名称'),
                        Infolists\Components\TextEntry::make('start_at')
                            ->label('开始时间')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('end_at')
                            ->label('结束时间')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('limit_per_user')
                            ->label('每人限领')
                            ->placeholder('不限'),
                    ]),
            ]);
    }
}
