<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Refunds\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class RefundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('退款单号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('用户'),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('租户'),
                Tables\Columns\TextColumn::make('order.no')
                    ->label('订单号')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('退款金额')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('refund_at')
                    ->label('退款时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('租户')
                    ->relationship(
                        name: 'tenant',
                        titleAttribute: 'name'
                    )
                    ->searchable()
                    ->preload(),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}

