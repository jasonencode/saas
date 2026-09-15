<?php

namespace App\Listeners\Schedule;

use App\Console\Commands\BaseCommand;
use App\Enums\System\ScheduleRunSource;
use App\Enums\System\ScheduleRunStatus;
use App\Models\System\ScheduleRunLog;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Console\Kernel;
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
class RecordManualCommandRun extends ScheduleListener
{
    /**
     * 命令开始
     */
    public function handleCommandStarting(CommandStarting $event): void
    {
        $this->safe(function () use ($event): void {
            $task = $event->command;

            if (!$this->shouldRecord($task)) {
                return;
            }

            $log = $this->createLog($task, null, ScheduleRunSource::Manual, $this->resolveLabel($task));

            ScheduleRunLog::rememberManualRun($task, (int) $log->getKey());
        });
    }

    /**
     * 命令结束
     */
    public function handleCommandFinished(CommandFinished $event): void
    {
        $this->safe(function () use ($event): void {
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
                'duration_ms' => $this->durationMs($log->started_at),
                'exception' => $event->exitCode === 0
                    ? null
                    : sprintf('[手动执行] 命令退出码 %d', $event->exitCode),
            ]);
        });
    }

    /**
     * 是否记录该命令的执行
     *
     * 判定逻辑：
     * 1. 调度器子进程会被注入 __LARAVEL_CONTEXT，手动执行不会有
     * 2. 调度器已登记 running 记录时跳过（仅对已注册的计划任务生效）
     *
     * @see docs/development/schedule-log.md 4.7
     */
    protected function shouldRecord(string $task): bool
    {
        // 判据 1：调度器子进程会被注入 __LARAVEL_CONTEXT，手动执行不会有
        if (getenv('__LARAVEL_CONTEXT') !== false) {
            return false;
        }

        // 判据 2：仅对已注册的计划任务，检查是否有 running 记录（防竞态）
        if ($this->isRegisteredTask($task)) {
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
        }

        return true;
    }

    /**
     * 获取已注册的计划任务签名（用于调试）
     */
    public static function getRegisteredTasks(): array
    {
        return (new static)->registeredTasks();
    }

    /**
     * 解析命令的中文名称
     */
    protected function resolveLabel(string $task): ?string
    {
        try {
            $kernel = app()->make(Kernel::class);
            $commands = $kernel->all();
            $command = $commands[$task] ?? null;

            if ($command instanceof BaseCommand) {
                return $command->getCommandLabel();
            }
        } catch (Throwable) {
            // 命令不存在或解析失败，忽略
        }

        return null;
    }
}
