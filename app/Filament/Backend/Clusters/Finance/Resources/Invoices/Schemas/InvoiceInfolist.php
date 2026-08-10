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
            ->components([
                Infolists\Components\TextEntry::make('user.name')
                    ->label('用户'),
                Infolists\Components\TextEntry::make('application.id')
                    ->label('申请ID')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('invoice_no')
                    ->label('发票号码'),
                Infolists\Components\TextEntry::make('invoice_date')
                    ->label('开票日期')
                    ->date('Y-m-d'),
                Infolists\Components\TextEntry::make('type')
                    ->label('发票类型')
                    ->badge(),
                Infolists\Components\TextEntry::make('amount')
                    ->label('发票金额')
                    ->money('CNY'),
                Infolists\Components\TextEntry::make('status')
                    ->label(__('backend.status'))
                    ->badge(),
                Infolists\Components\TextEntry::make('title_snapshot.title')
                    ->label('发票抬头')
                    ->placeholder('-'),
                Infolists\Components\TextEntry::make('title_snapshot.tax_no')
                    ->label('纳税人识别号')
                    ->placeholder('-'),
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
