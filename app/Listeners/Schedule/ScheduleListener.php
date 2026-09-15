<?php

namespace App\Listeners\Schedule;

use App\Enums\System\ScheduleRunSource;
use App\Enums\System\ScheduleRunStatus;
use App\Models\System\ScheduleRunLog;
use App\Models\System\System;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Str;
use Throwable;

/**
 * 计划任务日志监听器基类
 *
 * 提供自动调度与手动执行共用的基础能力：
 * - 任务名称解析（从调度事件或命令串中提取签名）
 * - 日志记录创建/更新
 * - 已注册任务白名单
 *
 * @see docs/development/schedule-log.md
 */
abstract class ScheduleListener
{
    /**
     * 摘要截断长度
     */
    protected const int SUMMARY_LIMIT = 2000;

    /**
     * 获取当前操作的系统用户（命令行）
     */
    protected function user(): System
    {
        return System::find(3);
    }

    /**
     * 解析调度事件的命令签名
     *
     * 项目内的计划任务均以字符串签名注册（Schedule::command('app:xxx')），
     * 此时 $event->command 形如 `'/usr/local/bin/php' 'artisan' app:xxx`，
     * 只能取命令串中 artisan 之后的第一段，与命令内 $this->getName() 保持一致。
     */
    protected function parseTaskName(string $command): string
    {
        // 匹配 artisan 后面的命令签名（artisan 可能被引号包裹）
        if (preg_match('/["\']?artisan["\']?\s+(\S+)/', $command, $matches) === 1) {
            return $matches[1];
        }

        // 兜底：去掉引号后取第一段
        return (string) Str::of($command)->trim('"\' ')->before(' ')->trim();
    }

    /**
     * 从调度事件中解析任务名称
     */
    protected function nameFromEvent($task): string
    {
        if ($task instanceof CallbackEvent) {
            return 'callback:'.substr(sha1($task->getSummaryForDisplay()), 0, 12);
        }

        return $this->parseTaskName((string) $task->command);
    }

    /**
     * 判断任务签名是否已注册为计划任务
     */
    protected function isRegisteredTask(string $task): bool
    {
        return in_array($task, $this->registeredTasks(), true);
    }

    /**
     * 获取已注册的计划任务签名
     *
     * 直接读调度器里真实注册的结果，而不是维护一份手工清单。
     * 非 console 上下文（未加载 routes/console.php）时返回空数组。
     *
     * @return array<int, string>
     */
    protected function registeredTasks(): array
    {
        return array_values(array_unique(array_map(
            fn ($task): string => $this->nameFromEvent($task),
            app(Schedule::class)->events()
        )));
    }

    /**
     * 创建执行日志记录
     */
    protected function createLog(string $task, ?string $expression, ScheduleRunSource $source, ?string $label = null): ScheduleRunLog
    {
        $startedAt = now();

        return ScheduleRunLog::create([
            'task' => $task,
            'label' => $label,
            'expression' => $expression,
            'server' => config('custom.server_id'),
            'status' => ScheduleRunStatus::Running,
            'source' => $source,
            'started_at' => $startedAt,
            'created_at' => $startedAt,
        ]);
    }

    /**
     * 计算耗时（毫秒）
     */
    protected function durationMs($startedAt): ?int
    {
        return $startedAt ? (int) round($startedAt->diffInMilliseconds(now())) : null;
    }

    /**
     * 安全地执行操作，捕获异常但不中断流程
     */
    protected function safe(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
