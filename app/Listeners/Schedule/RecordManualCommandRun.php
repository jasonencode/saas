<?php

namespace App\Listeners\Schedule;

use App\Enums\System\ScheduleRunSource;
use App\Enums\System\ScheduleRunStatus;
use App\Models\System\ScheduleRunLog;
use App\Support\ScheduledTask\ScheduledTask;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Throwable;

/**
 * 记录命令行手动执行的计划任务
 *
 * 调度链路（schedule:run）只覆盖自动调度；运维在服务器上手动补跑命令时走的是
 * CommandStarting / CommandFinished（由 ConsoleKernel 把 Symfony 控制台事件转派而来，
 * 仅在非测试环境自动开启），这里按签名白名单落一条 source=manual 的记录。
 *
 * 去重：调度器拉起的子进程同样会触发这两个事件，靠"进程标记 __LARAVEL_CONTEXT"
 * 与"父进程已登记 running 记录"两层判据识别并跳过，因此同一次执行只会有一条记录。
 *
 * @see docs/development/schedule-log.md 4.7
 */
class RecordManualCommandRun
{
    /**
     * 命令开始
     */
    public function handleCommandStarting(CommandStarting $event): void
    {
        try {
            $task = $event->command;

            if (!$this->shouldRecord($task)) {
                return;
            }

            $startedAt = now();

            $log = ScheduleRunLog::create([
                'task' => $task,
                'expression' => null,
                'server' => config('custom.server_id'),
                'status' => ScheduleRunStatus::Running,
                'source' => ScheduleRunSource::Manual,
                'started_at' => $startedAt,
                'created_at' => $startedAt,
            ]);

            ScheduleRunLog::rememberManualRun($task, (int) $log->getKey());
        } catch (Throwable $e) {
            // 日志写入失败不能影响命令本身
            report($e);
        }
    }

    /**
     * 命令结束
     */
    public function handleCommandFinished(CommandFinished $event): void
    {
        try {
            $task = $event->command;

            // 只有本进程登记过的执行才收尾；调度器子进程不会有这个键
            if (!$logId = ScheduleRunLog::manualLogId($task)) {
                return;
            }

            ScheduleRunLog::forgetManualRun($task);

            if (!$log = ScheduleRunLog::find($logId)) {
                return;
            }

            $log->update([
                'status' => $event->exitCode === 0 ? ScheduleRunStatus::Success : ScheduleRunStatus::Failed,
                'finished_at' => now(),
                'duration_ms' => $log->started_at
                    ? (int) round($log->started_at->diffInMilliseconds(now()))
                    : null,
                'exception' => $event->exitCode === 0
                    ? null
                    : sprintf('[手动执行] 命令退出码 %d', $event->exitCode),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * 是否记录该命令的执行
     *
     * 判定逻辑：
     * 1. 只记录已注册为计划任务的签名
     * 2. 调度器子进程会被注入 __LARAVEL_CONTEXT，手动执行不会有
     * 3. 注册表兜底：父进程已登记 running 记录时跳过（缓存失败时忽略）
     *
     * @see docs/development/schedule-log.md 4.7
     */
    protected function shouldRecord(string $task): bool
    {
        // 只记录已注册为计划任务的签名
        if (!ScheduledTask::isRegistered($task)) {
            return false;
        }

        // 判据 1：调度器子进程会被注入 __LARAVEL_CONTEXT，手动执行不会有
        if (getenv('__LARAVEL_CONTEXT') !== false) {
            return false;
        }

        // 判据 2：父进程已登记 running 记录，说明是调度链路的子进程
        // 缓存读取失败时忽略此判据，允许记录
        try {
            $logId = ScheduleRunLog::taskLogId($task);

            if ($logId) {
                $log = ScheduleRunLog::find($logId);

                if ($log && $log->isRunning()) {
                    return false;
                }
            }
        } catch (Throwable) {
            // 缓存/数据库异常时忽略，允许记录
        }

        return true;
    }

    /**
     * 获取已注册的计划任务签名（用于调试）
     */
    public static function getRegisteredTasks(): array
    {
        return ScheduledTask::registered();
    }
}
