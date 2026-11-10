<?php

namespace App\Filament\Tables\Foundation;

use App\Filament\Actions\Common\DisableBulkAction;
use App\Filament\Actions\Common\EnableBulkAction;
use App\Filament\Tables\Filters\TenantFilter;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class WechatPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge()
                    ->visible(fn (): bool => filament()->getTenant() === null),
                Tables\Columns\TextColumn::make('name')
                    ->label('支付名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('wechat.name')
                    ->label('关联微信')
                    ->badge()
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('wechat.app_id')
                    ->label('微信AppId')
                    ->copyable()
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('mch_id')
                    ->label('商户号')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TenantFilter::make()->visible(fn (): bool => filament()->getTenant() === null),
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
                    DisableBulkAction::make(),
                    EnableBulkAction::make(),
                ]),
            ]);
    }
}
