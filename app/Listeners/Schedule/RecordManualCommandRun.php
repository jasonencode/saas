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
     * 判定分两层，任一层命中都说明"这不是手动执行"：
     * 1. 进程标记：调度器拉起子进程时会注入 __LARAVEL_CONTEXT（见 Event::execute()），
     *    手动在终端执行不会有这个变量。这是确定性判据，不依赖缓存/数据库
     * 2. 注册表兜底：父进程已登记 running 记录（万一将来框架不再注入该变量）
     *
     * 另外只记录已注册为计划任务的签名（见 ScheduledTask::registered()），
     * 手动执行 migrate、tinker 之类的命令不应进这张表。
     */
    protected function shouldRecord(string $task): bool
    {
        if (!ScheduledTask::isRegistered($task)) {
            return false;
        }

        // 判据 1：调度器子进程会被注入 __LARAVEL_CONTEXT，手动执行不会有
        if (getenv('__LARAVEL_CONTEXT') !== false) {
            return false;
        }

        // 判据 2：父进程已登记 running 记录，说明这是调度链路的子进程
        $logId = ScheduleRunLog::taskLogId($task);

        if ($logId) {
            $log = ScheduleRunLog::find($logId);

            // 记录存在且正在运行，说明是调度器子进程，跳过
            if ($log && $log->isRunning()) {
                return false;
            }
        }

        // 判据 3：检查是否有任何 running 状态的记录（防竞态）
        // 如果 RecordScheduleRunLog 刚创建记录但缓存还没写入，用数据库兜底
        $hasRunningLog = ScheduleRunLog::where('task', $task)
            ->where('status', ScheduleRunStatus::Running)
            ->where('started_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($hasRunningLog) {
            return false;
        }

        return true;
    }
}
