<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Invoices\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->schema([
                Infolists\Components\TextEntry::make('id')
                    ->label('发票ID'),
                Infolists\Components\TextEntry::make('user.name')
                    ->label('用户'),
                Infolists\Components\TextEntry::make('application.id')
                    ->label('申请ID'),
                Infolists\Components\TextEntry::make('invoice_no')
                    ->label('发票号码'),
                Infolists\Components\TextEntry::make('invoice_date')
                    ->label('开票日期')
                    ->date(),
                Infolists\Components\TextEntry::make('type')
                    ->label('发票类型')
                    ->badge(),
                Infolists\Components\TextEntry::make('amount')
                    ->label('发票金额')
                    ->money('CNY'),
                Infolists\Components\TextEntry::make('status')
                    ->label('状态')
                    ->badge(),
                Infolists\Components\TextEntry::make('recipient_email')
                    ->label('接收邮箱'),
                Infolists\Components\TextEntry::make('recipient_phone')
                    ->label('接收电话'),
                Infolists\Components\TextEntry::make('creator')
                    ->label('开票人'),
                Infolists\Components\TextEntry::make('created_at')
                    ->label('创建时间'),
            ]);
    }
}
