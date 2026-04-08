<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\Accounts\RelationManagers;

use App\Enums\User\AccountAssetType;
use App\Enums\User\UserAccountLogType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $modelLabel = '账变日志';

    protected static ?string $title = '账变日志';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('变动类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('asset')
                    ->label('账户类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('变动前金额'),
                Tables\Columns\TextColumn::make('before')
                    ->label('变动前金额'),
                Tables\Columns\TextColumn::make('after')
                    ->label('变动后金额'),
                Tables\Columns\TextColumn::make('remark')
                    ->label('备注'),
                Tables\Columns\TextColumn::make('source_type')
                    ->label('来源类型'),
                Tables\Columns\TextColumn::make('source_id')
                    ->label('来源ID'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('变动时间'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('变动类型')
                    ->options(UserAccountLogType::class),
                Tables\Filters\SelectFilter::make('asset')
                    ->label('账户类型')
                    ->options(AccountAssetType::class),
            ]);
    }
}
