<?php

namespace App\Filament\Backend\Clusters\User\Resources\Socialites\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SocialitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('绑定用户'),
                TextColumn::make('account.provider')
                    ->label('第三方平台'),
                TextColumn::make('account.name')
                    ->label('平台名称'),
                TextColumn::make('provider_id')
                    ->label('身份标识'),
                TextColumn::make('union_id')
                    ->label('UnionId'),
                TextColumn::make('expired_at')
                    ->label('过期时间'),
                TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
