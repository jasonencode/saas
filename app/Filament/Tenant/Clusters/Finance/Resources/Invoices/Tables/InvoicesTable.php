<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Invoices\Tables;

use App\Enums\Finance\InvoiceStatus;
use App\Enums\Finance\InvoiceType;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('发票ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('用户')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_no')
                    ->label('发票号码')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('开票日期')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('发票类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('发票金额')
                    ->money('CNY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge(),
                Tables\Columns\TextColumn::make('creator')
                    ->label('开票人')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options(InvoiceStatus::class),
                Tables\Filters\SelectFilter::make('type')
                    ->label('发票类型')
                    ->options(InvoiceType::class),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make(),
                ]),
            ]);
    }
}
