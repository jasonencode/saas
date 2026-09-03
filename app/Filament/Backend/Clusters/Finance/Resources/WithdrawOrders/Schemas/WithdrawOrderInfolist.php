<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\WithdrawOrders\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class WithdrawOrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Fieldset::make('基本信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('no')
                            ->label('提现单号'),
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label('所属租户'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('用户'),
                    ]),
                Schemas\Components\Fieldset::make('提现信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('gateway')
                            ->label('提现方式')
                            ->badge(),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('提现金额')
                            ->money('cny'),
                        Infolists\Components\TextEntry::make('fee')
                            ->label('手续费')
                            ->money('cny'),
                        Infolists\Components\TextEntry::make('actual_amount')
                            ->label('实际到账')
                            ->money('cny'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('状态')
                            ->badge(),
                        Infolists\Components\TextEntry::make('account_info.name')
                            ->label('收款人')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('account_info.account')
                            ->label('收款账号')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('account_info.bank')
                            ->label('银行')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('remark')
                            ->label('备注')
                            ->placeholder('-'),
                    ]),
                Schemas\Components\Fieldset::make('审核信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('reviewer.name')
                            ->label('审核人')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('reviewed_at')
                            ->label('审核时间')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('reject_reason')
                            ->label('拒绝原因')
                            ->placeholder('-'),
                    ]),
                Schemas\Components\Fieldset::make('打款信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_no')
                            ->label('打款流水号')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('paid_at')
                            ->label('打款时间')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('创建时间'),
                    ]),
            ]);
    }
}
