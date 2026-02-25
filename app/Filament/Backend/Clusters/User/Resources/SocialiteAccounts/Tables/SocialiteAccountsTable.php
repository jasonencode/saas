<?php

namespace App\Filament\Backend\Clusters\User\Resources\SocialiteAccounts\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class SocialiteAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('租户'),
                Tables\Columns\TextColumn::make('provider')
                    ->label('平台类型'),
                Tables\Columns\TextColumn::make('name')
                    ->label('账户名称'),
                Tables\Columns\TextColumn::make('app_key')
                    ->label('APP_KEY'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
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
