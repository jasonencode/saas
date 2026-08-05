<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\Tables;

use Filament\Actions;
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
                    ->label('折扣值')
                    ->numeric(),
                Tables\Columns\TextColumn::make('min_amount')
                    ->label('最低消费')
                    ->numeric()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('expired_type')
                    ->label('过期方式')
                    ->badge(),
                Tables\Columns\TextColumn::make('days')
                    ->label('有效天数')
                    ->suffix('天')
                    ->placeholder('永久'),
                Tables\Columns\TextColumn::make('start_at')
                    ->label('开始时间')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('end_at')
                    ->label('结束时间')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('usage_limit')
                    ->label('发放数量')
                    ->numeric()
                    ->placeholder('不限'),
                Tables\Columns\TextColumn::make('usage_limit_per_user')
                    ->label('每人限领')
                    ->numeric()
                    ->placeholder('不限'),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status'))
                    ->sortable()
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
