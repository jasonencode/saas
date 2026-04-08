<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Networks\Tables;

use App\Models\BlockChain\Network;
use Filament\Actions;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;

class NetworksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
                Tables\Columns\TextColumn::make('name')
                    ->label('网络名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('主网类型')
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status')),
                Tables\Columns\IconColumn::make('explorer_url')
                    ->label('浏览器地址')
                    ->searchable()
                    ->icon(Heroicon::OutlinedWindow)
                    ->color('info')
                    ->url(fn (Network $network) => $network->explorer_url, true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
