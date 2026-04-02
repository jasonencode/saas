<?php

namespace App\Filament\Tenant\Clusters\Foundation\Resources\Socialites\Tables;

use App\Filament\Tables\Components\UserInfoColumn;
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
                UserInfoColumn::make(),
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
            ->filters([
                Tables\Filters\TrashedFilter::make(),
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
