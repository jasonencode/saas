<?php

namespace App\Filament\Infolists\Campaign;

use App\Filament\Widgets\Campaign\SeckillActivitySessionsChart;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class SeckillActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('基础信息')
                    ->columns(3)
                    ->schema([
                        Schemas\Components\Group::make()
                            ->columns()
                            ->columnSpan(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('tenant.name')
                                    ->label(__('backend.tenant'))
                                    ->badge()
                                    ->visible(fn (): bool => filament()->getTenant() === null),
                                Infolists\Components\TextEntry::make('name')
                                    ->label('活动名称'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('活动状态')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('sort')
                                    ->label(__('backend.sort')),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('活动说明')
                                    ->columnSpanFull()
                                    ->placeholder('-'),
                            ]),
                        Infolists\Components\ImageEntry::make('cover')
                            ->label('活动封面')
                            ->imageSize(120),
                    ]),
                Schemas\Components\Group::make()
                    ->schema([
                        Schemas\Components\Fieldset::make('时间信息')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('sessions_count')
                                    ->counts('sessions')
                                    ->label('场次数量'),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label(__('backend.created_at'))
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label(__('backend.updated_at'))
                                    ->dateTime(),
                            ]),
                        Schemas\Components\Livewire::make(SeckillActivitySessionsChart::class)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
