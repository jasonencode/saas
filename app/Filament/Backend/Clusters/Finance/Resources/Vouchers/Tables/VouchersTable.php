<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Vouchers\Tables;

use App\Enums\VoucherStatus;
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
                    ->label('计划名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('发起用户'),
                Tables\Columns\TextColumn::make('target.settlement_title')
                    ->label('结算目标'),
                Tables\Columns\TextColumn::make('status')
                    ->label('执行状态')
                    ->badge(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('完成时间'),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('计划执行时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('执行状态')
                    ->native(false)
                    ->options(VoucherStatus::class),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ]);
    }
}
