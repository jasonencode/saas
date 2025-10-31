<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\Modules\Tables;

use App\Filament\Actions\Setting\DisableModuleAction;
use App\Filament\Actions\Setting\EnableModuleAction;
use App\Filament\Actions\Setting\UninstallModuleAction;
use App\Services\ModuleService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;

class ModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->records(function (int $page, int $recordsPerPage): LengthAwarePaginator {
                return resolve(ModuleService::class)->getModules($page, $recordsPerPage);
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('模块名称')
                    ->searchable(),
                Tables\Columns\TextColumn::make('alias')
                    ->label('别名')
                    ->searchable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('排序')
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->label('激活状态'),
                Tables\Columns\TextColumn::make('author')
                    ->label('作者'),
                Tables\Columns\TextColumn::make('version')
                    ->label('版本')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('requires')
                    ->label('依赖')
                    ->badge(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EnableModuleAction::make(),
                DisableModuleAction::make(),
                //                UninstallModuleAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
