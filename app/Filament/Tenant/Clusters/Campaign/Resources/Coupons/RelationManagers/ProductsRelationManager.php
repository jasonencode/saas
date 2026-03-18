<?php

namespace App\Filament\Tenant\Clusters\Campaign\Resources\Coupons\RelationManagers;

use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = '关联商品';

    protected static ?string $modelLabel = '商品';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover')
                    ->label('封面图'),
                Tables\Columns\TextColumn::make('name')
                    ->label('商品名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('价格')
                    ->money('cny'),
            ])
            ->headerActions([
                Actions\AttachAction::make(),
            ])
            ->recordActions([
                Actions\DetachAction::make(),
            ]);
    }
}

