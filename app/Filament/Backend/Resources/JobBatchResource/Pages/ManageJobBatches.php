<?php

namespace App\Filament\Backend\Resources\JobBatchResource\Pages;

use App\Filament\Backend\Resources\JobBatchResource;
use App\Models\JobBatch;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Artisan;

class ManageJobBatches extends ManageRecords
{
    protected static string $resource = JobBatchResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function(Builder $query): Builder {
                return $query->orderByDesc('id');
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('任务名称')
                    ->description(fn(JobBatch $record): string => $record->id),
                Tables\Columns\TextColumn::make('process')
                    ->label('任务进度')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('total_jobs')
                    ->label('任务总数'),
                Tables\Columns\TextColumn::make('pending_jobs')
                    ->label('等待中任务'),
                Tables\Columns\TextColumn::make('failed_jobs')
                    ->label('失败任务'),
                Tables\Columns\TextColumn::make('processed_jobs')
                    ->label('已完成任务'),
                Tables\Columns\IconColumn::make('is_finished')
                    ->label('完成状态'),
                Tables\Columns\TextColumn::make('cancelled_at')
                    ->label('取消时间'),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('完成时间'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间'),
            ])
            ->actions([
                Tables\Actions\Action::make('cancel')
                    ->label('取消任务')
                    ->visible(fn(JobBatch $record) => userCan('cancel', $record))
                    ->hidden(fn(JobBatch $record): bool => $record->is_finished || $record->is_cancelled)
                    ->requiresConfirmation()
                    ->action(function(JobBatch $record, Tables\Actions\Action $action) {
                        $record->cancel();
                        $action->successNotificationTitle('取消成功');
                        $action->success();
                    }),
                Tables\Actions\Action::make('retry')
                    ->label('重试失败任务')
                    ->visible(fn(JobBatch $record) => userCan('retry', $record))
                    ->hidden(fn(JobBatch $record): bool => $record->failed_jobs === 0)
                    ->requiresConfirmation()
                    ->action(function(JobBatch $record, Tables\Actions\Action $action) {
                        Artisan::call('queue:retry-batch '.$record->id);
                        $action->successNotificationTitle('重试提交成功');
                        $action->success();
                    }),
            ]);
    }
}
