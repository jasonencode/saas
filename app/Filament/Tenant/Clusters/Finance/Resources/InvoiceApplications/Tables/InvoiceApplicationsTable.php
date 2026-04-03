<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\InvoiceApplications\Tables;

use App\Enums\InvoiceApplicationStatus;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('申请ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('申请人')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoiceTitle.title')
                    ->label('发票抬头')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('开票金额')
                    ->money('CNY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('开票原因')
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('申请时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options(InvoiceApplicationStatus::class),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make(),
                    Actions\EditAction::make(),
                ]),
            ]);
    }
}