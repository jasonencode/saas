<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Addresses\Tables;

use App\Filament\Tables\Components\UserInfoColumn;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class AddressesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                UserInfoColumn::make('user'),
                Tables\Columns\TextColumn::make('name')
                    ->label('联系人')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->label('手机号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_address')
                    ->label('完整地址')
                    ->wrap(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('默认')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
