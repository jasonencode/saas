<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Grid::make(1)
                    ->schema([
                        Schemas\Components\Section::make('基本信息')
                            ->columns(5)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('商品名称')
                                    ->size(TextSize::Large)
                                    ->weight('bold')
                                    ->columnSpan(2),
                                Infolists\Components\TextEntry::make('tenant.name')
                                    ->label(__('backend.tenant'))
                                    ->badge(),
                                Infolists\Components\TextEntry::make('storeConfigure.store_name')
                                    ->label('店铺名称'),
                                Infolists\Components\TextEntry::make('category.name')
                                    ->label('商品分类')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('brand.name')
                                    ->label('品牌')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('supplier.name')
                                    ->label('供应商')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('returnAddress.name')
                                    ->label('退货地址')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('tags.name')
                                    ->label('标签')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('商品简介')
                                    ->columnSpanFull()
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label(__('backend.status'))
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
                                Infolists\Components\TextEntry::make('delivery.name')
                                    ->label('运费模板')
                                    ->placeholder('-'),
                            ]),
                        Schemas\Components\Section::make('商品图片')
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
                Schemas\Components\Grid::make(1)
                    ->schema([
                        Schemas\Components\Section::make('价格与库存')
                            ->columns(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('origin_price')
                                    ->label('原价')
                                    ->prefix('¥'),
                                Infolists\Components\TextEntry::make('price')
                                    ->label('销售价')
                                    ->prefix('¥')
                                    ->weight('bold')
                                    ->color('primary'),
                                Infolists\Components\TextEntry::make('total_stock')
                                    ->label('库存')
                                    ->suffix(' 件'),
                                Infolists\Components\TextEntry::make('total_sale')
                                    ->label('销量')
                                    ->suffix(' 件'),
                            ]),
                        Schemas\Components\Section::make('扩展信息')
                            ->schema([
                                Infolists\Components\TextEntry::make('sort')
                                    ->label(__('backend.sort'))
                                    ->suffix(' (数字越大越靠前)'),
                                Infolists\Components\KeyValueEntry::make('ext')
                                    ->label('扩展信息')
                                    ->keyLabel('属性')
                                    ->valueLabel('值'),
                            ]),
                    ]),
            ]);
    }
}
