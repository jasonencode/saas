<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\WechatPayments\Tables;

use App\Filament\Actions\Common\DisableBulkAction;
use App\Filament\Actions\Common\EnableBulkAction;
use App\Models\Tenant;
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
                    ->label('所属租户')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('支付名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('wechat.name')
                    ->label('关联微信')
                    ->searchable(),
                Tables\Columns\TextColumn::make('wechat.app_id')
                    ->label('微信AppId')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('mch_id')
                    ->label('商户号')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('所属租户')
                    ->native(false)
                    ->options(Tenant::pluck('name', 'id')),
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\RestoreAction::make(),
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
