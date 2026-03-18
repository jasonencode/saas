<?php

namespace App\Filament\Backend\Clusters\Setting\Resources\FailedJobs\Tables;

use App\Models\FailedJob;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;

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
                    ->description(fn(FailedJob $record): string => $record->uuid),
                Tables\Columns\TextColumn::make('connection')
                    ->label('链接')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
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
                Actions\Action::make('retry')
                    ->label('重试')
                    ->icon('heroicon-c-arrow-path-rounded-square')
                    ->requiresConfirmation()
                    ->action(function (FailedJob $record, Actions\Action $action) {
                        Artisan::call('queue:retry '.$record->uuid);
                        $action->successNotificationTitle('操作成功');
                        $action->success();
                    })
                    ->visible(fn() => userCan('retry', $table->getModel())),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkAction::make('bulk_retry')
                    ->label('批量重试')
                    ->requiresConfirmation()
                    ->visible(fn() => userCan('bulkRetry', $table->getModel()))
                    ->action(function (Collection $records, Actions\BulkAction $action) {
                        $uuids = implode(' ', $records->pluck('uuid')->toArray());
                        Artisan::call('queue:retry '.$uuids);
                        $action->successNotificationTitle('操作成功');
                        $action->success();
                    }),
            ]);
    }
}
