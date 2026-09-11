<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Accounts\Tables;

use App\Filament\Actions\Finance\AdjustBalanceAction;
use App\Filament\Actions\Finance\AdjustPointsAction;
use App\Filament\Actions\Finance\FreezeBalanceAction;
use App\Filament\Actions\Finance\FreezePointsAction;
use App\Filament\Tables\Columns\UserInfoColumn;
use Filament\Actions;
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
                    ->money('cny')
                    ->sortable(),
                Tables\Columns\TextColumn::make('frozen_balance')
                    ->label('冻结余额')
                    ->money('cny')
                    ->sortable(),
                Tables\Columns\TextColumn::make('points')
                    ->label('积分')
                    ->sortable(),
                Tables\Columns\TextColumn::make('frozen_points')
                    ->label('冻结积分')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('backend.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    AdjustBalanceAction::make(),
                    AdjustPointsAction::make(),
                    FreezeBalanceAction::make(),
                    FreezePointsAction::make(),
                ]),
            ]);
    }
}
