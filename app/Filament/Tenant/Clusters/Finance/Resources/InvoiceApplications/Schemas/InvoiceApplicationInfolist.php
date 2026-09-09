<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\InvoiceApplications\Schemas;

use Filament\Infolists;
use Filament\Schemas;
use Filament\Schemas\Schema;

class InvoiceApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Grid::make()
                    ->columns(1)
                    ->schema([
                        Schemas\Components\Fieldset::make('基本信息')
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('申请人'),
                                Infolists\Components\TextEntry::make('status')
                                    ->label(__('backend.status'))
                                    ->badge(),
                            ]),
                        Schemas\Components\Fieldset::make('开票信息')
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('title_snapshot.title')
                                    ->label('发票抬头')
                                    ->placeholder('未设置'),
                                Infolists\Components\TextEntry::make('title_snapshot.tax_no')
                                    ->label('纳税人识别号')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('amount')
                                    ->label('开票金额')
                                    ->money('CNY'),
                                Infolists\Components\TextEntry::make('reason')
                                    ->label('开票原因')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Schemas\Components\Grid::make()
                    ->columns(1)
                    ->schema([
                        Schemas\Components\Fieldset::make('审核信息')
                            ->schema([
                                Infolists\Components\TextEntry::make('remark')
                                    ->label('备注')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ]),
                        Schemas\Components\Fieldset::make('时间信息')
                            ->columns()
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('申请时间')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('更新时间')
                                    ->dateTime(),
                            ]),
                    ]),
            ]);
    }
}
