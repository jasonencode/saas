<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Deliveries\Tables;

use App\Enums\Mall\DeliveryType;
use App\Filament\Actions\Common\UpgradeSortAction;
use App\Filament\Tables\Filters\TenantFilter;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeliveriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderBy('is_default', 'desc')->latest())
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
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
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('backend.sort'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                TenantFilter::make(),
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
                Actions\ViewAction::make(),
                Actions\ActionGroup::make([
                    UpgradeSortAction::make(),
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
