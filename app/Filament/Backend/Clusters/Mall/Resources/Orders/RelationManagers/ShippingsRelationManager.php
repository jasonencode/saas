<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingsRelationManager extends RelationManager
{
    protected static string $relationship = 'shippings';

    protected static ?string $title = '发货记录';

    protected static ?string $modelLabel = '发货记录';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('express.name')
                    ->label('快递名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('express_no')
                    ->label('快递单号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('收件人')
                    ->searchable(),
                Tables\Columns\TextColumn::make('mobile')
                    ->label('手机号')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_address')
                    ->label('完整地址'),
                Tables\Columns\TextColumn::make('delivery_at')
                    ->label('发货时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sign_at')
                    ->label('签收时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ]);
    }
}
