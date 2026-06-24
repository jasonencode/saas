<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Schemas;

use Filament\Infolists;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Number;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('订单信息')
                    ->columns(3)
                    ->components([
                        Infolists\Components\TextEntry::make('no')
                            ->label('订单编号')
                            ->copyable()
                            ->badge(),
                        Infolists\Components\TextEntry::make('status')
                            ->label('订单状态')
                            ->badge(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('下单时间')
                            ->icon(Heroicon::OutlinedCalendar),
                        Infolists\Components\TextEntry::make('paid_at')
                            ->label('支付时间')
                            ->placeholder('未支付'),
                        Infolists\Components\TextEntry::make('remark')
                            ->label('买家备注')
                            ->placeholder('无'),
                        Infolists\Components\TextEntry::make('seller_remark')
                            ->label('商家备注')
                            ->placeholder('无')
                            ->color('warning'),
                    ]),
                Section::make('金额信息')
                    ->columns(3)
                    ->components([
                        Infolists\Components\TextEntry::make('amount')
                            ->label('商品金额')
                            ->formatStateUsing(fn ($state) => Number::currency($state, 'CNY')),
                        Infolists\Components\TextEntry::make('freight')
                            ->label('运费')
                            ->formatStateUsing(fn ($state) => Number::currency($state, 'CNY')),
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('订单总额')
                            ->formatStateUsing(fn ($state) => Number::currency($state, 'CNY'))
                            ->weight('bold')
                            ->size(TextSize::Large),
                        Infolists\Components\TextEntry::make('products_count')
                            ->label('商品数量')
                            ->suffix(' 种'),
                        Infolists\Components\TextEntry::make('items_quantity')
                            ->label('总数量')
                            ->suffix(' 件'),
                    ]),
                Section::make('收货地址')
                    ->columns()
                    ->components([
                        Infolists\Components\TextEntry::make('address.name')
                            ->label('收货人')
                            ->icon(Heroicon::OutlinedUser),
                        Infolists\Components\TextEntry::make('address.mobile')
                            ->label('联系电话')
                            ->copyable()
                            ->icon(Heroicon::OutlinedPhone),
                        Infolists\Components\TextEntry::make('address.full_address')
                            ->label('详细地址')
                            ->columnSpanFull(),
                    ]),
                Section::make('物流信息')
                    ->columns()
                    ->components([
                        Infolists\Components\TextEntry::make('expresses.express.name')
                            ->label('快递公司')
                            ->default('-'),
                        Infolists\Components\TextEntry::make('expresses.express_no')
                            ->label('物流单号')
                            ->copyable()
                            ->default('-'),
                        Infolists\Components\TextEntry::make('expresses.delivery_at')
                            ->label('发货时间')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('expresses.sign_at')
                            ->label('签收时间')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
