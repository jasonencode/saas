<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Wechats\Tables;

use App\Filament\Actions\Common\DisableBulkAction;
use App\Filament\Actions\Common\EnableBulkAction;
use App\Filament\Actions\Foundation\TestWechatConnection;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class WechatsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('微信名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('app_id')
                    ->label('AppId')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->translateLabel(),
                Tables\Columns\IconColumn::make('connection')
                    ->label('连接状态'),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
            ])
            ->recordActions([
                TestWechatConnection::make(),
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
