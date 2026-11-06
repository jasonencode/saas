<?php

namespace App\Filament\Infolists\Campaign;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class SeckillSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('基础信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label(__('backend.tenant'))
                            ->badge()
                            ->visible(fn (): bool => filament()->getTenant() === null),
                        Infolists\Components\TextEntry::make('activity.name')
                            ->label('所属活动'),
                        Infolists\Components\TextEntry::make('name')
                            ->label('场次名称'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('场次状态')
                            ->badge(),
                        Infolists\Components\TextEntry::make('sort')
                            ->label(__('backend.sort')),
                    ]),
                Schemas\Components\Fieldset::make('关联商品')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\ImageEntry::make('product.cover')
                            ->label('商品封面')
                            ->imageSize(120),
                        Infolists\Components\TextEntry::make('product.name')
                            ->label('商品名称')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('product.status')
                            ->label('商品状态')
                            ->badge(),
                        Infolists\Components\TextEntry::make('sku.name')
                            ->label('规格'),
                        Infolists\Components\TextEntry::make('sku.price')
                            ->label('规格原价')
                            ->money('cny'),
                        Infolists\Components\TextEntry::make('sku.stock')
                            ->label('规格库存'),
                    ]),
                Schemas\Components\Fieldset::make('秒杀信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('seckill_price')
                            ->label('秒杀价')
                            ->money('cny'),
                        Infolists\Components\TextEntry::make('stock')
                            ->label('秒杀名额'),
                        Infolists\Components\TextEntry::make('sold')
                            ->label('已售'),
                        Infolists\Components\TextEntry::make('remaining')
                            ->label('剩余名额'),
                        Infolists\Components\TextEntry::make('limit_per_user')
                            ->label('每人限购'),
                        Infolists\Components\TextEntry::make('start_at')
                            ->label('开抢时间')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('end_at')
                            ->label('结束时间')
                            ->dateTime(),
                    ]),
                Schemas\Components\Fieldset::make('时间信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('backend.created_at'))
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('backend.updated_at'))
                            ->dateTime(),
                    ]),
            ]);
    }
}
