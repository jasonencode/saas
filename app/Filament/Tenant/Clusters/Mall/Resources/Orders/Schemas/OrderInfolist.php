<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\Schemas;

use Filament\Infolists;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 订单基本信息
                Section::make('订单信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('no')
                            ->label('订单编号')
                            ->copyable()
                            ->badge(),
                        Infolists\Components\TextEntry::make('status')
                            ->label('订单状态')
                            ->badge(),
                        Infolists\Components\TextEntry::make('user.username')
                            ->label('下单用户')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label('所属租户')
                            ->icon('heroicon-o-building-office'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('下单时间')
                            ->dateTime('Y-m-d H:i:s')
                            ->icon('heroicon-o-calendar'),
                        Infolists\Components\TextEntry::make('paid_at')
                            ->label('支付时间')
                            ->dateTime('Y-m-d H:i:s')
                            ->placeholder('未支付'),
                        Infolists\Components\TextEntry::make('expired_at')
                            ->label('过期时间')
                            ->dateTime('Y-m-d H:i:s'),
                    ])->columns(3),

                // 订单金额信息
                Section::make('金额信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('amount')
                            ->label('商品金额')
                            ->money('CNY')
                            ->suffix('元'),
                        Infolists\Components\TextEntry::make('freight')
                            ->label('运费')
                            ->money('CNY')
                            ->suffix('元'),
                        Infolists\Components\TextEntry::make('total_amount')
                            ->label('订单总额')
                            ->money('CNY')
                            ->suffix('元')
                            ->weight('bold')
                            ->size(TextSize::Large),
                    ])->columns(3),

                // 收货地址信息
                Section::make('收货地址')
                    ->schema([
                        Infolists\Components\TextEntry::make('address.name')
                            ->label('收货人')
                            ->icon('heroicon-o-user'),
                        Infolists\Components\TextEntry::make('address.mobile')
                            ->label('联系电话')
                            ->copyable()
                            ->icon('heroicon-o-phone'),
                        Infolists\Components\TextEntry::make('address.full_address')
                            ->label('详细地址')
                            ->columnSpanFull(),
                    ])->columns(),

                // 物流信息（如果有）
                Section::make('物流信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('expresses.express.name')
                            ->label('快递公司')
                            ->default('暂无物流'),
                        Infolists\Components\TextEntry::make('expresses.express_no')
                            ->label('物流单号')
                            ->copyable()
                            ->default('无'),
                        Infolists\Components\TextEntry::make('expresses.delivery_at')
                            ->label('发货时间')
                            ->placeholder('未发货'),
                        Infolists\Components\TextEntry::make('expresses.sign_at')
                            ->label('签收时间')
                            ->dateTime('Y-m-d H:i:s')
                            ->placeholder('未签收'),
                    ])->columns(),
            ]);
    }
}
