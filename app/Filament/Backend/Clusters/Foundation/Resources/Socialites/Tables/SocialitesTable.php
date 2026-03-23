<?php

namespace App\Filament\Backend\Clusters\Foundation\Resources\Socialites\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class SocialitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('绑定用户'),
                Tables\Columns\TextColumn::make('account.provider')
                    ->label('第三方平台'),
                Tables\Columns\TextColumn::make('account.name')
                    ->label('平台名称'),
                Tables\Columns\TextColumn::make('provider_id')
                    ->label('身份标识'),
                Tables\Columns\TextColumn::make('union_id')
                    ->label('UnionId'),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('过期时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at')),
            ])
            ->recordActions([
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
