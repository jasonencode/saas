<?php

namespace App\Filament\Backend\Clusters\BlockChain\Resources\Contracts\Tables;

use App\Models\Contract;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('租户'),
                Tables\Columns\TextColumn::make('name')
                    ->label('合约名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('合约地址')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('hash')
                    ->label('上链哈希')
                    ->limit(6)
                    ->suffix(fn(Contract $contract) => substr($contract->hash, 60))
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('deployer.address')
                    ->label('部署账户')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remark')
                    ->label('备注信息'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
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
