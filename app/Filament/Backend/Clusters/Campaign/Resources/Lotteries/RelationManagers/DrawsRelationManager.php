<?php

namespace App\Filament\Backend\Clusters\Campaign\Resources\Lotteries\RelationManagers;

use App\Filament\Tables\Components\UserInfoColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DrawsRelationManager extends RelationManager
{
    protected static string $relationship = 'draws';

    protected static ?string $title = '抽奖记录';

    protected static ?string $modelLabel = '抽奖记录';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('prize.name')
                    ->label('奖品')
                    ->default('谢谢参与'),
                Tables\Columns\TextColumn::make('draw_cost_type')
                    ->label('消耗类型')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'free' => '免费',
                        'points' => '积分',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'free' => 'success',
                        'points' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('draw_cost_amount')
                    ->label('消耗数量')
                    ->default(0),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('draw_cost_type')
                    ->label('消耗类型')
                    ->options([
                        'free' => '免费',
                        'points' => '积分',
                    ]),
            ]);
    }
}
