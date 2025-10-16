<?php

namespace App\Filament\Backend\Clusters\Settings\Resources\FailedJobs\Pages;

use App\Filament\Backend\Clusters\Settings\Resources\FailedJobs\FailedJobResource;
use App\Models\FailedJob;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Artisan;

class ManageFailedJobs extends ManageRecords
{
    protected static string $resource = FailedJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('clean')
                ->label('清理失败任务')
                ->icon('heroicon-o-trash')
                ->color(Color::Red)
                ->requiresConfirmation()
                ->visible(fn() => userCan('clean', $this->getModel()))
                ->action(function(Actions\Action $action) {
                    Artisan::call('queue:flush');
                    $action->successNotificationTitle('操作成功');
                    $action->success();
                }),
            Actions\Action::make('retryAll')
                ->label('重试所有失败任务')
                ->icon('heroicon-o-receipt-refund')
                ->color(Color::Green)
                ->visible(fn() => userCan('retryAll', $this->getModel()))
                ->requiresConfirmation()
                ->action(function(Actions\Action $action) {
                    Artisan::call('queue:retry all');
                    $action->successNotificationTitle('操作成功');
                    $action->success();
                }),
            Actions\Action::make('retryQueue')
                ->label('重试指定队列')
                ->icon('heroicon-m-arrows-pointing-out')
                ->visible(fn() => userCan('retryQueue', $this->getModel()))
                ->schema(function() {
                    return [
                        Forms\Components\Select::make('name')
                            ->label('队列名')
                            ->required()
                            ->native(false)
                            ->options(FailedJob::select('queue')->distinct()->pluck('queue', 'queue')),
                    ];
                })
                ->action(function(array $data, Actions\Action $action) {
                    Artisan::call('queue:retry --queue='.$data['name']);
                    $action->successNotificationTitle('操作成功');
                    $action->success();
                }),
        ];
    }
}
