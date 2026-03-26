<?php

namespace App\Filament\Tenant\Clusters\Finance\Resources\Accounts\Tables;

use App\Filament\Actions\Finance\AdjustAccountAction;
use App\Filament\Actions\Finance\FreezeAccountAction;
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
                    ->label(__('backend.created_at'))
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
