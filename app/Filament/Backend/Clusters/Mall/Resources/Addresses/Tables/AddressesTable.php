<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Addresses\Tables;

use App\Filament\Actions\Common\SetDefaultAction;
use App\Filament\Actions\Common\UpgradeSortAction;
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
                UserInfoColumn::make(),
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
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    SetDefaultAction::make(),
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
