<?php

namespace App\Filament\Backend\Clusters\Mall\Resources\Regions\Tables;

use App\Enums\Mall\RegionLevel;
use App\Filament\Actions\Common\UpgradeSortAction;
use App\Models\Mall\Region;
use Filament\Actions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class RegionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#ID#'),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('上级地区')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('name')
                    ->label('地区名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pinyin')
                    ->label('地区拼音')
                    ->searchable(),
                Tables\Columns\TextColumn::make('level')
                    ->label('地区级别')
                    ->badge(),
                Tables\Columns\TextColumn::make('sort')
                    ->label(__('backend.sort'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('地区级别')
                    ->options(RegionLevel::class),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    UpgradeSortAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
