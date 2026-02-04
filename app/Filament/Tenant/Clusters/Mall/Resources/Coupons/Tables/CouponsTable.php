<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Coupons\Tables;

use App\Models\Coupon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('优惠券名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('优惠券代码')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('优惠券类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('value')
                    ->label('折扣值'),
                Tables\Columns\TextColumn::make('expired_type')
                    ->label('有效期类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('expired_date')
                    ->label('有效期')
                    ->description(fn(Coupon $record) => $record->end_at),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态')
                    ->sortable()
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

