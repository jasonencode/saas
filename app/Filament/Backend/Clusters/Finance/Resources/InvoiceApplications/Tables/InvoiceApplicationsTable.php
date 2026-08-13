<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\InvoiceApplications\Tables;

use App\Enums\Finance\InvoiceApplicationStatus;
use App\Filament\Actions\Finance\IssueInvoiceAction;
use App\Filament\Tables\Components\UserInfoColumn;
use App\Filament\Tables\Filters\TenantFilter;
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
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
                UserInfoColumn::make()
                    ->label('申请人'),
                Tables\Columns\TextColumn::make('invoiceTitle.title')
                    ->label('发票抬头')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('开票金额')
                    ->money('CNY')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('开票原因')
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('申请时间')
                    ->sortable(),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('backend.status'))
                    ->options(InvoiceApplicationStatus::class),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    IssueInvoiceAction::make(),
                    Actions\EditAction::make(),
                ]),
            ]);
    }
}
