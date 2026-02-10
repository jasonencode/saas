<?php

namespace App\Filament\Backend\Clusters\User\Resources\Accounts\Tables;

use App\Filament\Actions\User\AdjustAccountAction;
use App\Filament\Actions\User\FreezeAccountAction;
use App\Filament\Tables\Components\UserInfoColumn;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('user_id', 'desc')
            ->columns([
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('余额')
                    ->sortable(),
                Tables\Columns\TextColumn::make('frozen_balance')
                    ->label('冻结余额')
                    ->sortable(),
                Tables\Columns\TextColumn::make('points')
                    ->label('积分')
                    ->sortable(),
                Tables\Columns\TextColumn::make('frozen_points')
                    ->label('冻结积分')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                AdjustAccountAction::make(),
                FreezeAccountAction::make(),
            ]);
    }
}
