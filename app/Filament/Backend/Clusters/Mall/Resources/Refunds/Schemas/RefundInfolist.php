<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Refunds\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class RefundInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make('退款信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('no')
                            ->label('退款单号')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('用户')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label(__('backend.tenant'))
                            ->badge(),
                        Infolists\Components\TextEntry::make('order.no')
                            ->label('订单号')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('type')
                            ->label('退款类型')
                            ->badge(),
                        Infolists\Components\TextEntry::make('reason')
                            ->label('退款原因')
                            ->badge(),
                        Infolists\Components\TextEntry::make('reason_detail')
                            ->label('原因详情')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Schemas\Components\Grid::make(1)
                    ->schema([
                        Schemas\Components\Section::make('金额信息')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('goods_amount')
                                    ->label('商品金额')
                                    ->money('CNY'),
                                Infolists\Components\TextEntry::make('freight_amount')
                                    ->label('运费金额')
                                    ->money('CNY'),
                                Infolists\Components\TextEntry::make('total')
                                    ->label('退款总额')
                                    ->money('CNY')
                                    ->weight('bold'),
                            ]),
                        Schemas\Components\Section::make('状态与时间')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label(__('backend.status'))
                                    ->badge(),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('申请时间')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('refund_at')
                                    ->label('退款时间')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('approval_remark')
                                    ->label('审核备注')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
