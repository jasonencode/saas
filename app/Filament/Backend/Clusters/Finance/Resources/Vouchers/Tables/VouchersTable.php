<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Vouchers\Tables;

use App\Enums\Finance\VoucherStatus;
use App\Filament\Tables\Filters\TenantFilter;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class VouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
                Tables\Columns\TextColumn::make('no')
                    ->label('结算单号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('计划名称')
                    ->badge()
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('发起用户'),
                Tables\Columns\TextColumn::make('target.settlement_title')
                    ->label('结算目标')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->sortable()
                    ->label('完成时间')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->sortable()
                    ->label('计划执行时间')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('backend.status'))
                    ->options(VoucherStatus::class),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\ActionGroup::make([
                    Actions\EditAction::make(),
                ]),
            ]);
    }
}
