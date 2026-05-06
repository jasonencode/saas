<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\FailedJobs\Tables;

use App\Filament\Actions\Setting\RetryBulkFailedJobsAction;
use App\Filament\Actions\Setting\RetrySingleFailedJobAction;
use App\Models\System\FailedJob;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class FailedJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('failed_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id'),
                Tables\Columns\TextColumn::make('payload.displayName')
                    ->label('任务名称')
                    ->description(fn (FailedJob $record): string => $record->uuid),
                Tables\Columns\TextColumn::make('connection')
                    ->label('链接')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'redis' => 'danger',
                        'database' => 'success',
                    }),
                Tables\Columns\TextColumn::make('queue')
                    ->label('队列名称')
                    ->badge(),
                Tables\Columns\TextColumn::make('failed_at')
                    ->label('失败时间'),
            ])
            ->recordActions([
                Actions\ViewAction::make(),
                RetrySingleFailedJobAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                RetryBulkFailedJobsAction::make(),
            ]);
    }
}
