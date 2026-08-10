<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Vouchers\Tables;

use App\Enums\Finance\VoucherStatus;
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
                Tables\Columns\TextColumn::make('no')
                    ->label('结算单号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('结算计划')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('target.settlement_title')
                    ->label('结算目标')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('backend.status'))
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('完成时间')
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('计划执行时间')
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('backend.status'))
                    ->options(VoucherStatus::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make(),
                ]),
            ]);
    }
}
