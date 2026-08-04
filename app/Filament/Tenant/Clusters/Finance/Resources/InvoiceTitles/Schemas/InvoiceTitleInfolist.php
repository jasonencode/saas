<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\InvoiceTitles\Schemas;

use App\Enums\Finance\InvoiceTitleType;
use App\Models\Finance\InvoiceTitle;
use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class InvoiceTitleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make('基础信息')
                    ->columns()
                    ->schema([
                        Infolists\Components\TextEntry::make('type')
                            ->label('类型')
                            ->badge(),
                        Infolists\Components\TextEntry::make('title')
                            ->label('抬头名称'),
                        Infolists\Components\TextEntry::make('tax_no')
                            ->label('纳税人识别号'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('邮箱'),
                        Infolists\Components\IconEntry::make('is_default')
                            ->label('默认')
                            ->boolean(),
                    ]),
                Schemas\Components\Section::make('企业信息')
                    ->columns()
                    ->visible(fn (InvoiceTitle $record) => $record->type === InvoiceTitleType::Enterprise)
                    ->schema([
                        Infolists\Components\TextEntry::make('company_address')
                            ->label('企业地址')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('company_phone')
                            ->label('企业电话'),
                        Infolists\Components\TextEntry::make('bank_name')
                            ->label('开户行'),
                        Infolists\Components\TextEntry::make('bank_account')
                            ->label('银行账号')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
