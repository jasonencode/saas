<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\PickupPoints\Tables;

use App\Filament\Tables\Filters\TenantFilter;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PickupPointsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->bySort())
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
                Tables\Columns\TextColumn::make('name')
                    ->label('自提点名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact')
                    ->label('联系人'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('联系电话')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_address')
                    ->label('详细地址')
                    ->copyable(),
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('backend.sort')),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\TernaryFilter::make('status')
                    ->label(__('backend.status')),
                Tables\Filters\TrashedFilter::make(),
            ]);
    }
}
