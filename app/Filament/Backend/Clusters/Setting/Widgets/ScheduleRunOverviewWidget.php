<?php

namespace App\Filament\Backend\Clusters\Setting\Widgets;

use App\Enums\System\ScheduleRunStatus;
use App\Filament\Backend\Clusters\Setting\Resources\ScheduleRunLogs\ScheduleRunLogResource;
use App\Models\System\ScheduleRunLog;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * 计划任务运行概览
 *
 * 口径见 docs/development/schedule-log.md 4.3：
 * "今日"按 app 时区计算；成功率只统计已落终态的记录。
 */
class ScheduleRunOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = Cache::remember(
            'schedule_run:overview:'.now()->toDateString(),
            now()->addMinutes(5),
            static function (): array {
                $today = today();

                $finishedQuery = ScheduleRunLog::query()
                    ->where('started_at', '>=', $today)
                    ->whereIn('status', [ScheduleRunStatus::Success, ScheduleRunStatus::Failed]);

                $finished = (clone $finishedQuery)->count();
                $success = (clone $finishedQuery)->where('status', ScheduleRunStatus::Success)->count();

                $lastFailed = ScheduleRunLog::query()
                    ->where('status', ScheduleRunStatus::Failed)
                    ->latest('started_at')
                    ->first();

                return [
                    'today_total' => ScheduleRunLog::query()->where('started_at', '>=', $today)->count(),
                    'today_finished' => $finished,
                    'today_success' => $success,
                    'running' => ScheduleRunLog::query()->where('status', ScheduleRunStatus::Running)->count(),
                    'last_failed_task' => $lastFailed?->label(),
                    'last_failed_at' => $lastFailed?->started_at?->diffForHumans(),
                    'last_failed_reason' => $lastFailed?->exception
                        ? Str::limit(Str::squish($lastFailed->exception), 60)
                        : null,
                    'last_failed_url' => $lastFailed
                        ? ScheduleRunLogResource::getUrl('view', ['record' => $lastFailed])
                        : null,
                ];
            }
        );

        $rate = $stats['today_finished'] > 0
            ? round($stats['today_success'] / $stats['today_finished'] * 100, 1).'%'
            : '-';

        return [
            Stat::make('今日执行次数', $stats['today_total'])
                ->description('不含执行中的记录')
                ->descriptionIcon(Heroicon::OutlinedPlayCircle)
                ->color('info')
                ->url(ScheduleRunLogResource::getIndexUrl()),

            Stat::make('今日成功率', $rate)
                ->description($stats['today_finished'] > 0
                    ? "成功 {$stats['today_success']} / 终态 {$stats['today_finished']}"
                    : '今日暂无终态记录')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color($stats['today_finished'] > 0 && $stats['today_success'] === $stats['today_finished'] ? 'success' : 'warning')
                ->url(ScheduleRunLogResource::getIndexUrl()),

            Stat::make('执行中任务', $stats['running'])
                ->description($stats['running'] > 0 ? '存在未落终态的任务，留意是否卡死' : '当前没有执行中的任务')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color($stats['running'] > 0 ? 'warning' : 'gray')
                ->url(ScheduleRunLogResource::getIndexUrl()),

            Stat::make('最近失败任务', $stats['last_failed_task'] ?? '无')
                ->description($stats['last_failed_at']
                    ? $stats['last_failed_at'].' · '.($stats['last_failed_reason'] ?? '')
                    : '近期没有失败记录')
                ->descriptionIcon(Heroicon::OutlinedExclamationCircle)
                ->color($stats['last_failed_task'] ? 'danger' : 'success')
                ->url($stats['last_failed_url']),
        ];
    }
}
