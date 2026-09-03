<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\RechargeOrders\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class RechargeOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('基本信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('no')
                            ->label('充值单号'),
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label('所属租户'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('用户'),
                    ]),
                Schemas\Components\Fieldset::make('充值信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('type')
                            ->label('充值类型')
                            ->badge(),
                        Infolists\Components\TextEntry::make('gateway')
                            ->label('支付网关')
                            ->badge(),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('充值金额')
                            ->money('cny'),
                        Infolists\Components\TextEntry::make('received_amount')
                            ->label('到账金额')
                            ->money('cny'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('状态')
                            ->badge(),
                        Infolists\Components\TextEntry::make('payment_no')
                            ->label('支付流水号')
                            ->placeholder('-'),
                    ]),
                Schemas\Components\Fieldset::make('时间信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('paid_at')
                            ->label('支付时间')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('完成时间')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('expired_at')
                            ->label('过期时间')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('创建时间'),
                    ]),
            ]);
    }
}
