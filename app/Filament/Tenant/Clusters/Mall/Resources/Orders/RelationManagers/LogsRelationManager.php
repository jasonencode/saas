<?php

namespace App\Filament\Tenant\Clusters\Mall\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = '订单日志';

    protected static ?string $modelLabel = '订单日志';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->label('操作')
                    ->badge(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('操作用户')
                    ->placeholder('系统'),
                Tables\Columns\TextColumn::make('remark')
                    ->label('操作内容'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('操作时间')
                    ->sortable(),
            ]);
    }
}
