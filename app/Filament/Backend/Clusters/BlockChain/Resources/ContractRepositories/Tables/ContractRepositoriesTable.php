<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\ContractRepositories\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class ContractRepositoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('合约名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('version')
                    ->label('版本号')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('contract_name')
                    ->label('主合约名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('compiler_version')
                    ->label('Solidity 版本')
                    ->searchable(),
                Tables\Columns\TextColumn::make('source_name')
                    ->label('.sol 文件'),
                Tables\Columns\IconColumn::make('status')
                    ->label(__('backend.status'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')
                    ->label(__('backend.status')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\ActionGroup::make([
                    Actions\EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                    Actions\ForceDeleteBulkAction::make(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
