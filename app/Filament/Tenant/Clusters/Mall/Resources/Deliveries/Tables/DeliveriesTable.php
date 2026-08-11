<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Deliveries\Tables;

use App\Enums\Mall\DeliveryType;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class DeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('模板名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('计费方式')
                    ->badge(),
                Tables\Columns\TextColumn::make('first')
                    ->label('首件/首重'),
                Tables\Columns\TextColumn::make('first_fee')
                    ->label('首费(元)')
                    ->money('CNY'),
                Tables\Columns\TextColumn::make('additional')
                    ->label('续件/续重'),
                Tables\Columns\TextColumn::make('additional_fee')
                    ->label('续费(元)')
                    ->money('CNY'),
                Tables\Columns\TextColumn::make('free_shipping_threshold')
                    ->label('包邮门槛')
                    ->money('CNY'),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('默认')
                    ->boolean(),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('计费方式')
                    ->options(DeliveryType::class),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('默认模板'),
                Tables\Filters\TernaryFilter::make('status')
                    ->label(__('backend.status')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make(),
                    Actions\DeleteAction::make(),
                    Actions\RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
