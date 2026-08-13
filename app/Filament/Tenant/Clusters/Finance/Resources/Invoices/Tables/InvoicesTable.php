<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Invoices\Tables;

use App\Enums\Finance\InvoiceStatus;
use App\Enums\Finance\InvoiceType;
use App\Filament\Tables\Components\UserInfoColumn;
use Filament\Tables;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('invoice_no')
                    ->label('发票号码')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('开票日期')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('发票类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('发票金额')
                    ->money('CNY'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->badge(),
                Tables\Columns\TextColumn::make('creator')
                    ->label('开票人'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('backend.status'))
                    ->options(InvoiceStatus::class),
                Tables\Filters\SelectFilter::make('type')
                    ->label('发票类型')
                    ->options(InvoiceType::class),
            ]);
    }
}
