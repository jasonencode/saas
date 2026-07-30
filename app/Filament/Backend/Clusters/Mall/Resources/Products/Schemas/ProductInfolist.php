<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products\Schemas;

use Filament\Infolists;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(1)
                    ->schema([
                        Section::make('基本信息')
                            ->schema([
                                Infolists\Components\TextEntry::make('tenant.name')
                                    ->label('租户')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('storeConfigure.store_name')
                                    ->label('店铺名称'),
                                Infolists\Components\TextEntry::make('category.name')
                                    ->label('商品分类'),
                                Infolists\Components\TextEntry::make('brand.name')
                                    ->label('品牌')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('商品简介')
                                    ->columnSpanFull()
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label('商品状态')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('deduct_stock_type')
                                    ->label('库存扣减方式')
                                    ->badge(),
                                Infolists\Components\IconEntry::make('can_cart')
                                    ->label('可加入购物车')
                                    ->boolean(),
                                Infolists\Components\TextEntry::make('views')
                                    ->label('浏览量')
                                    ->suffix(' 次'),
                            ])->columns(4),
                        Section::make('商品图片')
                            ->collapsible()
                            ->schema([
                                Infolists\Components\ImageEntry::make('cover')
                                    ->label('封面图')
                                    ->imageSize(120),
                                Infolists\Components\ImageEntry::make('pictures')
                                    ->label('轮播图')
                                    ->imageSize(80)
                                    ->square()
                                    ->columnSpanFull(),
                                Infolists\Components\ImageEntry::make('materials')
                                    ->label('详情图片集')
                                    ->imageSize(80)
                                    ->square()
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Grid::make(1)
                    ->schema([
                        Section::make('价格与库存')
                            ->schema([
                                Infolists\Components\TextEntry::make('origin_price')
                                    ->label('原价'),
                                Infolists\Components\TextEntry::make('price')
                                    ->label('销售价')
                                    ->weight('bold')
                                    ->color('primary'),
                                Infolists\Components\TextEntry::make('total_stock')
                                    ->label('库存')
                                    ->suffix(' 件'),
                                Infolists\Components\TextEntry::make('total_sale')
                                    ->label('销量')
                                    ->suffix(' 件'),
                            ])->columns(4),
                        Section::make('商品规格')
                            ->collapsible()
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('skus')
                                    ->label('规格列表')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('规格名称')
                                            ->columnSpan(2),
                                        Infolists\Components\TextEntry::make('code')
                                            ->label('规格编号')
                                            ->placeholder('-'),
                                        Infolists\Components\ImageEntry::make('cover')
                                            ->label('规格图片')
                                            ->imageSize(60),
                                        Infolists\Components\TextEntry::make('origin_price')
                                            ->label('原价')
                                            ->html()
                                            ->formatStateUsing(fn ($state) => blank($state) ? '-' : '<s>¥'.number_format((float) $state, 2).'</s>'),
                                        Infolists\Components\TextEntry::make('price')
                                            ->label('销售价')
                                            ->money('CNY')
                                            ->weight('bold')
                                            ->size(TextSize::Large)
                                            ->color('primary'),
                                        Infolists\Components\TextEntry::make('stock')
                                            ->label('库存')
                                            ->suffix(' 件'),
                                        Infolists\Components\TextEntry::make('sale')
                                            ->label('销量')
                                            ->suffix(' 件'),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),
                            ]),
                        Section::make('扩展信息')
                            ->schema([
                                Infolists\Components\TextEntry::make('sort')
                                    ->label('排序')
                                    ->suffix(' (数字越大越靠前)'),
                                Infolists\Components\KeyValueEntry::make('ext')
                                    ->label('扩展信息')
                                    ->keyLabel('属性')
                                    ->valueLabel('值')
                                    ->columnSpanFull(),
                            ])->columns(),
                    ]),
            ]);
    }
}
