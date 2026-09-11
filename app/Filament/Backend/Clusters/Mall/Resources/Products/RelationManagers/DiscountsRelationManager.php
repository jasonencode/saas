<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountsRelationManager extends RelationManager
{
    protected static string $relationship = 'discounts';

    protected static ?string $modelLabel = '身份折扣';

    protected static ?string $title = '身份折扣';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('身份名称'),
                Tables\Columns\TextColumn::make('percent')
                    ->label('折扣')
                    ->formatStateUsing(fn ($state): string => sprintf('%s 折（%d%%）', rtrim(rtrim(number_format((float) $state / 10, 1, '.', ''), '0'), '.'), $state)),
                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('创建时间')
                    ->dateTime(),
            ]);
    }
}
