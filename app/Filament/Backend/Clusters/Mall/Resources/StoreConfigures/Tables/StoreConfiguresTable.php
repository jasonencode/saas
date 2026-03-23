<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\StoreConfigures\Tables;

use App\Filament\Tables\Filters\TenantFilter;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class StoreConfiguresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('cover')
                    ->label('店铺LOGO'),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('店铺名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contactor')
                    ->label('联系人')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('联系方式')
                    ->searchable(),
            ])
            ->filters([
                TenantFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
