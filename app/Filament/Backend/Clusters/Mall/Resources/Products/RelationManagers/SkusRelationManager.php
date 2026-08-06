<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Products\RelationManagers;

use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SkusRelationManager extends RelationManager
{
    protected static string $relationship = 'skus';

    protected static ?string $modelLabel = 'SKU';

    protected static ?string $title = 'SKU';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover')
                    ->label('规格图片'),
                Tables\Columns\TextColumn::make('name')
                    ->label('规格名称'),
                Tables\Columns\TextColumn::make('code')
                    ->label('规格编号(69码)'),
                Tables\Columns\TextColumn::make('origin_price')
                    ->label('原价(划线价)'),
                Tables\Columns\TextColumn::make('price')
                    ->label('销售价')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('stock')
                    ->label('库存')
                    ->money('cny'),
                Tables\Columns\TextColumn::make('sale')
                    ->label('销量'),
                Tables\Columns\TextColumn::make('weight')
                    ->label('重量(KG)'),
                Tables\Columns\TextColumn::make('volume')
                    ->label('体积(m³)'),
                Tables\Columns\TextColumn::make('sort')
                    ->label('排序'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }
}
