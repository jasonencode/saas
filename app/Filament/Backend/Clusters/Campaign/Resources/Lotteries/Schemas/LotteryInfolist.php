<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Lotteries\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class LotteryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('基础信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->label('活动名称'),
                        Infolists\Components\TextEntry::make('draw_mode')
                            ->label('抽奖模式')
                            ->badge(),
                        Infolists\Components\TextEntry::make('cover')
                            ->label('活动封面'),
                        Infolists\Components\TextEntry::make('description')
                            ->label('活动描述')
                            ->columnSpanFull()
                            ->placeholder('无描述'),
                    ]),
                Schemas\Components\Fieldset::make('抽奖配置')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('free_draws_per_day')
                            ->label('每日免费次数'),
                        Infolists\Components\TextEntry::make('points_per_draw')
                            ->label('每次消耗积分')
                            ->suffix('积分'),
                        Infolists\Components\TextEntry::make('max_draws_per_user')
                            ->label('每人总次数上限')
                            ->placeholder('不限'),
                    ]),
                Schemas\Components\Fieldset::make('活动时间')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('start_at')
                            ->label('开始时间'),
                        Infolists\Components\TextEntry::make('end_at')
                            ->label('结束时间'),
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label(__('backend.tenant'))
                            ->badge(),
                    ]),
                Schemas\Components\Fieldset::make('状态')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\IconEntry::make('status')
                            ->label(__('backend.status'))
                            ->boolean(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('backend.created_at')),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('backend.updated_at')),
                    ]),
            ]);
    }
}
