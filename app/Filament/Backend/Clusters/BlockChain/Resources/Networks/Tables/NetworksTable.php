<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Networks\Tables;

use App\Models\Network;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                    ->label('租户')
                    ->badge(),
                Tables\Columns\TextColumn::make('name')
                    ->label('网络名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('主网类型')
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('状态'),
                Tables\Columns\IconColumn::make('explorer_url')
                    ->label('浏览器地址')
                    ->searchable()
                    ->icon(Heroicon::OutlinedWindow)
                    ->color('info')
                    ->url(fn(Network $network) => $network->explorer_url, true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
