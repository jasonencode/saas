<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Schemas;

use App\Enums\Mall\FulfillmentType;
use Deldius\UserField\UserEntry;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('订单信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('no')
                            ->label('订单编号')
                            ->copyable()
                            ->badge(),
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('backend.status'))
                            ->badge(),
                        Infolists\Components\TextEntry::make('fulfillment_type')
                            ->label('履约方式')
                            ->state(fn ($record) => $record->fulfillment_type?->getLabel() ?? '-')
                            ->badge()
                            ->color(fn ($record) => $record->fulfillment_type?->getColor()),
                        UserEntry::make('user')
                            ->label('下单用户'),
                        Infolists\Components\TextEntry::make('items_quantity')
                            ->label('总数量')
                            ->suffix(' 件'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('下单时间'),
                        Infolists\Components\TextEntry::make('paid_at')
                            ->label('支付时间')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('remark')
                            ->label('买家备注')
                            ->columnSpanFull()
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('seller_remark')
                            ->label('商家备注')
                            ->columnSpanFull()
                            ->placeholder('-')
                            ->color('warning'),
                    ]),
                Schemas\Components\Grid::make(1)
                    ->schema([
                        Schemas\Components\Fieldset::make('金额信息')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('amount')
                                    ->label('商品金额')
                                    ->money('cny'),
                                Infolists\Components\TextEntry::make('freight')
                                    ->label('运费')
                                    ->money('cny'),
                                Infolists\Components\TextEntry::make('total_amount')
                                    ->label('订单总额')
                                    ->money('cny')
                                    ->weight('bold')
                                    ->size(TextSize::Large),

                            ]),
                        Schemas\Components\Fieldset::make('收货地址')
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('address.name')
                                    ->label('收货人')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('address.mobile')
                                    ->label('联系电话')
                                    ->copyable()
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('address.full_address')
                                    ->label('详细地址')
                                    ->columnSpanFull()
                                    ->placeholder('-'),
                            ]),
                        Schemas\Components\Fieldset::make('自提信息')
                            ->columns()
                            ->visible(fn ($record) => $record?->fulfillment_type === FulfillmentType::Pickup)
                            ->schema([
                                Infolists\Components\TextEntry::make('pickup_code')
                                    ->label('核销码')
                                    ->copyable()
                                    ->badge()
                                    ->color('warning'),
                                Infolists\Components\TextEntry::make('pickupPoint.name')
                                    ->label('自提点')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('pickupPoint.full_address')
                                    ->label('自提点地址')
                                    ->columnSpanFull()
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('verified_at')
                                    ->label('核销时间')
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }
}
