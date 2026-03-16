<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\WechatMinis\Tables;

use App\Filament\Actions\Common\DisableBulkAction;
use App\Filament\Actions\Common\EnableBulkAction;
use App\Models\Tenant;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class WechatMinisTable
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
                    ->label('小程序名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('app_id')
                    ->label('AppId')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态'),
                Tables\Columns\IconColumn::make('is_connected')
                    ->label('连接状态'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('所属租户')
                    ->native(false)
                    ->options(fn() => Tenant::pluck('name', 'id')),
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
