<?php

namespace App\Filament\Tenant\Clusters\User\Resources\Identities\RelationManagers;

use App\Enums\User\IdentityChannel;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class IdentityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'afterLogs';

    protected static ?string $modelLabel = '身份变更记录';

    protected static ?string $title = '身份变更记录';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('用户')
                    ->searchable(),
                Tables\Columns\TextColumn::make('beforeIdentity.name')
                    ->label('变更前身份')
                    ->placeholder('（无）'),
                Tables\Columns\IconColumn::make('beforeIdentity.name')
                    ->label('')
                    ->icon('heroicon-o-arrow-right')
                    ->size('xs'),
                Tables\Columns\TextColumn::make('afterIdentity.name')
                    ->label('变更后身份'),
                Tables\Columns\TextColumn::make('channel')
                    ->label('变更渠道')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('变更时间'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->label('变更渠道')
                    ->options(IdentityChannel::class),
            ]);
    }
}
