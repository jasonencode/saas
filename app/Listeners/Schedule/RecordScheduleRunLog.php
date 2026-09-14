<?php

namespace App\Listeners\Schedule;

use App\Enums\System\ScheduleRunStatus;
use App\Models\System\ScheduleRunLog;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Support\Str;
use Throwable;

/**
 * 记录计划任务执行日志
 *
 * 三个事件都在 schedule:run 父进程内触发，命令本身跑在独立子进程中：
 * - 未抢到 onOneServer 锁的节点、被过滤条件跳过（withoutOverlapping / when / 维护模式 / schedule:pause）的执行不触发事件，因此不产生记录
 * - 子进程 stderr 默认丢弃，非零退出码时 Failed 携带的是框架包装的 exit code 异常（业务真因见 storage/logs/laravel-*.log，或由 ->storeOutput() 采集）
 *
 * @see routes/console.php
 */
class RecordScheduleRunLog
{
    /**
     * 摘要截断长度
     */
    protected const int SUMMARY_LIMIT = 2000;

    /**
     * 任务开始
     */
    public function handleStarting(ScheduledTaskStarting $event): void
    {
        try {
            $task = $this->taskName($event->task);
            $expression = (string) $event->task->expression;
            $startedAt = now();

            $log = ScheduleRunLog::create([
                'task' => $task,
                'expression' => $expression,
                'server' => config('custom.server_id'),
                'status' => ScheduleRunStatus::Running,
                'started_at' => $startedAt,
                'created_at' => $startedAt,
            ]);

            ScheduleRunLog::rememberRun($task, $expression, (int) $log->getKey());
        } catch (Throwable $e) {
            // 日志写入失败不能中断 schedule:run
            report($e);
        }
    }

    /**
     * 任务结束
     */
    public function handleFinished(ScheduledTaskFinished $event): void
    {
        $this->updateRun($event->task, [
            'status' => ScheduleRunStatus::Success,
            'finished_at' => now(),
            'duration_ms' => (int) round($event->runtime * 1000),
        ], onlyWhileRunning: true);
    }

    /**
     * 任务失败
     */
    public function handleFailed(ScheduledTaskFailed $event): void
    {
        $this->updateRun($event->task, [
            'status' => ScheduleRunStatus::Failed,
            'finished_at' => now(),
            'exception' => $this->exceptionSummary($event),
        ], onlyWhileRunning: false);
    }

    /**
     * 按运行注册表定位本次记录并写入终态
     *
     * 非零退出码时框架会先发 Finished 再补发 Failed，因此这里：
     * - 不清除注册表，否则 Failed 找不到记录，失败会被永久记成成功
     * - Finished 只允许改写"执行中"的记录，Failed 允许改写"执行中/成功"的记录（终态为失败）
     *
     * @param  array<string, mixed>  $attributes  待写入字段
     * @param  bool  $onlyWhileRunning  是否只允许改写"执行中"的记录
     */
    protected function updateRun(ScheduledEvent $task, array $attributes, bool $onlyWhileRunning): void
    {
        try {
            if (!$log = $this->currentLog($task)) {
                return;
            }

            if ($log->isFailed() || ($onlyWhileRunning && !$log->isRunning())) {
                return;
            }

            // 进程启动失败 / before-callback 抛错时不会有 Finished，用开始时间兜底；
            // 已有耗时（Finished 写入）时保持不动
            if (!array_key_exists('duration_ms', $attributes) && $log->duration_ms === null) {
                $attributes['duration_ms'] = $log->started_at
                    ? (int) round($log->started_at->diffInMilliseconds(now()))
                    : null;
            }

            // 已捕获的输出同样只补空，避免 Failed 覆盖 Finished 写入的值
            if (!array_key_exists('output', $attributes) && $log->output === null) {
                $attributes['output'] = $this->capturedOutput($task);
            }

            $log->update($attributes);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * 获取本次运行的日志记录
     */
    protected function currentLog(ScheduledEvent $task): ?ScheduleRunLog
    {
        $logId = ScheduleRunLog::runLogId($this->taskName($task), (string) $task->expression);

        return $logId ? ScheduleRunLog::find($logId) : null;
    }

    /**
     * 解析任务标识
     *
     * 项目内的计划任务均以字符串签名注册（Schedule::command('app:xxx')），
     * 此时 $event->command 形如 `php artisan app:xxx --flag=1` 且 description 为 null，
     * 只能取命令串中 artisan 之后的第一段，与命令内 $this->getName() 保持一致。
     * 注意 Windows 下 php 与 artisan 两段都可能被引号包裹：
     * `"D:\php\php.exe" "artisan" app:xxx`。
     */
    protected function taskName(ScheduledEvent $task): string
    {
        if ($task instanceof CallbackEvent) {
            return 'callback:'.substr(sha1((string) $task->getSummaryForDisplay()), 0, 12);
        }

        $command = (string) $task->command;

        if (preg_match('/"?(?:artisan|artisan\.php)"?\s+(\S+)/', $command, $matches) === 1) {
            return $matches[1];
        }

        return (string) Str::of($command)->trim('"\' ')->before(' ')->trim();
    }

    /**
     * 生成失败摘要
     *
     * 非零退出码时框架抛出的是 exit code 包装异常，不含业务异常原栈。
     */
    protected function exceptionSummary(ScheduledTaskFailed $event): string
    {
        $exception = $event->exception;

        $summary = sprintf('[%s] %s', $exception::class, $exception->getMessage());

        if ($output = $this->capturedOutput($event->task)) {
            $summary .= "\n\n--- 命令输出 ---\n".$output;
        }

        return Str::limit($summary, self::SUMMARY_LIMIT);
    }

    /**
     * 读取命令输出尾部
     *
     * 仅当任务声明了 ->storeOutput() 时可用（默认输出到 /dev/null 或 NUL，不采集）。
     * 输出文件按任务固定且只追加不轮转，尾部可能带出上一次执行的结尾。
     */
    protected function capturedOutput(ScheduledEvent $task): ?string
    {
        $path = $task->output;

        if (!is_string($path) || !is_file($path) || !is_readable($path)) {
            return null;
        }

        $contents = trim((string) file_get_contents($path));

        return $contents === '' ? null : mb_substr($contents, -self::SUMMARY_LIMIT);
    }
}
