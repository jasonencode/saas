<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Invoices\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Grid::make(1)
                    ->schema([
                        Schemas\Components\Section::make('基础信息')
                            ->icon('heroicon-o-document-text')
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('invoice_no')
                                    ->label('发票号码')
                                    ->copyable()
                                    ->fontFamily('mono'),
                                Infolists\Components\TextEntry::make('invoice_date')
                                    ->label('开票日期')
                                    ->date('Y-m-d'),
                                Infolists\Components\TextEntry::make('type')
                                    ->label('发票类型')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('status')
                                    ->label(__('backend.status'))
                                    ->badge(),
                            ]),
                        Schemas\Components\Section::make('抬头信息')
                            ->icon('heroicon-o-identification')
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('title_snapshot.title')
                                    ->label('发票抬头')
                                    ->placeholder('未设置'),
                                Infolists\Components\TextEntry::make('title_snapshot.tax_no')
                                    ->label('纳税人识别号')
                                    ->placeholder('无'),
                            ]),
                        Schemas\Components\Section::make('金额信息')
                            ->icon('heroicon-o-currency-yen')
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('amount')
                                    ->label('发票金额')
                                    ->money('CNY')
                                    ->color('success'),
                            ]),
                        Schemas\Components\Section::make('联系信息')
                            ->icon('heroicon-o-envelope')
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('recipient_email')
                                    ->label('接收邮箱')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('recipient_phone')
                                    ->label('接收电话')
                                    ->copyable(),
                            ]),
                    ]),
                Schemas\Components\Grid::make(1)
                    ->schema([
                        Schemas\Components\Section::make('申请信息')
                            ->icon('heroicon-o-paper-clip')
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('application.amount')
                                    ->label('申请金额')
                                    ->money('CNY'),
                                Infolists\Components\TextEntry::make('application.status')
                                    ->label('申请状态')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('application.remark')
                                    ->label('申请备注')
                                    ->columnSpanFull(),
                            ]),
                        Schemas\Components\Section::make('用户信息')
                            ->icon('heroicon-o-user')
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('用户'),
                                Infolists\Components\TextEntry::make('creator')
                                    ->label('开票人'),
                            ]),
                        Schemas\Components\Section::make('系统信息')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('创建时间')
                                    ->dateTime('Y-m-d H:i:s'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('更新时间')
                                    ->dateTime('Y-m-d H:i:s'),
                            ]),
                    ]),
            ]);
    }
}
