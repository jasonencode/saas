<?php

namespace App\Filament\Infolists\Finance;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Schemas\Components\Fieldset::make('基本信息')
                    ->columns(3)
                    ->columnSpan(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label('租户')
                            ->visible(fn(): bool => filament()->getTenant() === null),
                        Infolists\Components\TextEntry::make('no')
                            ->label('支付单号'),
                        Infolists\Components\TextEntry::make('paymentable.title')
                            ->label('支付对象')
                            ->badge()
                            ->color('amber')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('gateway')
                            ->label('支付网关')
                            ->badge(),
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('backend.status'))
                            ->badge(),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('支付金额')
                            ->money('cny'),
                        Infolists\Components\TextEntry::make('user.username')
                            ->label('用户'),
                        Infolists\Components\TextEntry::make('user.profile.nickname')
                            ->label('昵称')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('paid_at')
                            ->label('支付时间')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
                Schemas\Components\Group::make()
                    ->schema([
                        Schemas\Components\Fieldset::make('支付环境')
                            ->columns(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('ip')
                                    ->label('支付 IP')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('user_agent')
                                    ->label('支付设备')
                                    ->placeholder('-')
                                    ->columnSpan(2),
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
                                Infolists\Components\TextEntry::make('deleted_at')
                                    ->label('删除时间')
                                    ->dateTime()
                                    ->visible(fn($record): bool => $record->trashed())
                                    ->placeholder('-'),
                            ]),
                    ])
            ]);
    }
}
