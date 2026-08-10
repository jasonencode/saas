<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Vouchers\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class VoucherInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make('基础信息')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('no')
                            ->label('结算单号')
                            ->badge()
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('plan.name')
                            ->label('结算计划')
                            ->badge(),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('发起用户')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('backend.status'))
                            ->badge(),
                    ]),
                Schemas\Components\Section::make('结算目标')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('target.settlement_title')
                            ->label('结算目标')
                            ->placeholder('-'),
                    ]),
                Schemas\Components\Section::make('时间信息')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('scheduled_at')
                            ->label('计划执行时间')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('完成时间')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('backend.created_at'))
                            ->placeholder('-'),
                    ]),
                Schemas\Components\Section::make('异常信息')
                    ->schema([
                        Infolists\Components\TextEntry::make('exception')
                            ->label('异常信息')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
