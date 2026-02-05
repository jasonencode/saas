<?php

namespace App\Filament\Backend\Clusters\User\Resources\Accounts\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('user_id', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('用户'),
                TextColumn::make('balance')
                    ->label('余额')
                    ->sortable(),
                TextColumn::make('frozen_balance')
                    ->label('冻结余额')
                    ->sortable(),
                TextColumn::make('points')
                    ->label('积分')
                    ->sortable(),
                TextColumn::make('frozen_points')
                    ->label('冻结积分')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
