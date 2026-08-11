<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\JobBatches\Tables;

use App\Filament\Actions\Setting\CancelJobBatchAction;
use App\Filament\Actions\Setting\RetryJobBatchAction;
use App\Models\System\JobBatch;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class JobBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('任务名称')
                    ->description(fn (JobBatch $record): string => $record->id),
                Tables\Columns\TextColumn::make('process')
                    ->label('任务进度')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('total_jobs')
                    ->label('任务总数')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pending_jobs')
                    ->label('等待中任务')
                    ->sortable(),
                Tables\Columns\TextColumn::make('failed_jobs')
                    ->label('失败任务')
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_jobs')
                    ->label('已完成任务'),
                Tables\Columns\IconColumn::make('is_finished')
                    ->label('完成状态'),
                Tables\Columns\TextColumn::make('cancelled_at')
                    ->label('取消时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('完成时间')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('backend.created_at'))
                    ->sortable(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    CancelJobBatchAction::make(),
                    RetryJobBatchAction::make(),
                ]),
            ]);
    }
}
