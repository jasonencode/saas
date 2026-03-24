<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Refunds\Schemas;

use App\Filament\Infolists\Components\TextareaEntry;
use Deldius\UserField\UserEntry;
use Filament\Infolists;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RefundInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('基本信息')
                    ->columns(3)
                    ->components([
                        Infolists\Components\TextEntry::make('no')
                            ->label('退款单号'),
                        Infolists\Components\TextEntry::make('tenant.name')
                            ->label(__('backend.tenant'))
                            ->badge(),
                        Infolists\Components\TextEntry::make('paymentOrder.no')
                            ->label('关联支付单号'),
                        UserEntry::make('created_by')
                            ->label('申请人'),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('退款金额')
                            ->money('cny'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('退款状态')
                            ->badge(),
                    ]),

                Section::make('退款详情')
                    ->columns(1)
                    ->components([
                        TextareaEntry::make('reason')
                            ->label('退款原因')
                            ->rows(3),
                    ]),

                Section::make('审核信息')
                    ->columns(3)
                    ->components([
                        Infolists\Components\TextEntry::make('approver.name')
                            ->label('审核人')
                            ->placeholder('待审核'),
                        Infolists\Components\TextEntry::make('approved_at')
                            ->label('审核时间')
                            ->placeholder('待审核'),
                        TextareaEntry::make('rejected_reason')
                            ->label('拒绝原因')
                            ->rows(2)
                            ->placeholder('-'),
                    ]),

                Section::make('时间信息')
                    ->columns(3)
                    ->components([
                        Infolists\Components\TextEntry::make('refunded_at')
                            ->label('退款完成时间')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('创建时间'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('更新时间'),
                    ]),

                Section::make('设备信息')
                    ->columns()
                    ->collapsible()
                    ->components([
                        Infolists\Components\TextEntry::make('ip')
                            ->label('IP地址'),
                        Infolists\Components\TextEntry::make('user_agent')
                            ->label('设备信息')
                            ->limit(),
                    ]),
            ]);
    }
}
