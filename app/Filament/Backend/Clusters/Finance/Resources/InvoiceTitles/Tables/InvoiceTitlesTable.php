<?php

namespace App\Filament\Backend\Clusters\Finance\Resources\InvoiceTitles\Tables;

use App\Enums\Finance\InvoiceTitleType;
use App\Filament\Actions\Common\SetDefaultAction;
use App\Filament\Tables\Components\UserInfoColumn;
use App\Filament\Tables\Filters\TenantFilter;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceTitlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label(__('backend.tenant'))
                    ->badge(),
                UserInfoColumn::make(),
                Tables\Columns\TextColumn::make('type')
                    ->label('类型')
                    ->badge(),
                Tables\Columns\TextColumn::make('title')
                    ->label('抬头名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tax_no')
                    ->label('税号')
                    ->placeholder('-')
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('邮箱')
                    ->placeholder('-'),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('默认')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->filters([
                TenantFilter::make(),
                Tables\Filters\SelectFilter::make('type')
                    ->label('类型')
                    ->options(InvoiceTitleType::class),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('默认抬头'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    SetDefaultAction::make(),
                    Actions\EditAction::make(),
                    Actions\DeleteAction::make(),
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
