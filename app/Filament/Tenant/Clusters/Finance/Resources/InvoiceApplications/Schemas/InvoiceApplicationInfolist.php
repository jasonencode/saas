<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\InvoiceApplications\Schemas;

use Filament\Infolists;
use Filament\Schemas\Schema;

class InvoiceApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Infolists\Components\TextEntry::make('id')
                    ->label('申请ID'),
                Infolists\Components\TextEntry::make('user.name')
                    ->label('申请人'),
                Infolists\Components\TextEntry::make('invoiceTitle.title')
                    ->label('发票抬头'),
                Infolists\Components\TextEntry::make('amount')
                    ->label('开票金额')
                    ->money('CNY'),
                Infolists\Components\TextEntry::make('reason')
                    ->label('开票原因'),
                Infolists\Components\TextEntry::make('status')
                    ->label('状态')
                    ->badge(),
                Infolists\Components\TextEntry::make('remark')
                    ->label('备注'),
                Infolists\Components\TextEntry::make('created_at')
                    ->label('申请时间'),
                Infolists\Components\TextEntry::make('updated_at')
                    ->label('更新时间'),
            ]);
    }
}