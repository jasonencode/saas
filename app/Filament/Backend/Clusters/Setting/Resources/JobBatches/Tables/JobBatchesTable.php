<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\JobBatches\Tables;

use App\Models\JobBatch;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class JobBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('任务名称')
                    ->description(fn(JobBatch $record): string => $record->id),
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
                Actions\Action::make('cancel')
                    ->label('取消任务')
                    ->visible(fn(JobBatch $record) => userCan('cancel', $record))
                    ->hidden(fn(JobBatch $record): bool => $record->is_finished || $record->is_cancelled)
                    ->requiresConfirmation()
                    ->action(function (JobBatch $record, Actions\Action $action) {
                        $record->cancel();
                        $action->successNotificationTitle('取消成功');
                        $action->success();
                    }),
                Actions\Action::make('retry')
                    ->label('重试失败任务')
                    ->visible(fn(JobBatch $record) => userCan('retry', $record))
                    ->hidden(fn(JobBatch $record): bool => $record->failed_jobs === 0)
                    ->requiresConfirmation()
                    ->action(function (JobBatch $record, Actions\Action $action) {
                        Artisan::call('queue:retry-batch '.$record->id);
                        $action->successNotificationTitle('重试提交成功');
                        $action->success();
                    }),
            ]);
    }
}
