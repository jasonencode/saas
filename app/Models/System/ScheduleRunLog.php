<?php

namespace App\Models\System;

use App\Enums\System\ScheduleRunStatus;
use App\Models\Model;
use App\Policies\System\ScheduleRunLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * 计划任务执行日志
 *
 * 由 Schedule\RecordScheduleRunLog 监听调度事件写入，业务命令经 logContext() 补充业务摘要。
 *
 * @property ScheduleRunStatus $status
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 * @property array<string, mixed>|null $context
 */
#[Unguarded]
#[UsePolicy(ScheduleRunLogPolicy::class)]
class ScheduleRunLog extends Model
{
    use Prunable;

    /**
     * 运行注册表 TTL（秒）
     *
     * 需覆盖"任务开始 → 命令内最后一次 logContext()"的时长，超时后埋点会静默丢失。
     */
    public const int REGISTRY_TTL = 3600;

    /**
     * 任务签名与后台展示名映射
     *
     * 未登记的签名直接展示原文，新增计划任务时按需补充。
     */
    public const array TASK_LABELS = [
        'queue:prune-batches' => '清理队列批次',
        'sanctum:prune-expired' => '清理 Sanctum 令牌',
        'model:prune' => '清理模型数据',
        'app:mall:order-auto-complete' => '订单自动完成',
        'app:user:identity-expire' => '身份过期清理',
        'app:campaign:coupon-expire' => '优惠券过期清理',
    ];

    const null UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'status' => ScheduleRunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'context' => 'array',
        ];
    }

    /**
     * 获取可修剪的模型查询
     *
     * @return Builder<ScheduleRunLog>
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subDays(90));
    }

    /**
     * 是否执行中
     */
    public function isRunning(): bool
    {
        return $this->status === ScheduleRunStatus::Running;
    }

    /**
     * 是否执行失败
     */
    public function isFailed(): bool
    {
        return $this->status === ScheduleRunStatus::Failed;
    }

    /**
     * 业务上下文是否有失败计数
     *
     * 命令内部捕获的逐条失败不会改变执行状态（进程仍正常退出），
     * 只能通过 logContext(['failed' => n]) 表达，此处作为后台提示依据。
     */
    public function hasErrors(): bool
    {
        return (int) ($this->context['failed'] ?? 0) > 0;
    }

    /**
     * 获取任务展示名
     */
    public function label(): string
    {
        return self::TASK_LABELS[$this->task] ?? (string) $this->task;
    }

    /**
     * 格式化耗时
     *
     * @param  int|null  $milliseconds  耗时毫秒
     *
     * @return string 人性化文本（如 350ms / 1.2s / 2m 5s）
     */
    public static function formatDuration(?int $milliseconds): string
    {
        if ($milliseconds === null) {
            return '-';
        }

        if ($milliseconds < 1000) {
            return $milliseconds.'ms';
        }

        if ($milliseconds < 60000) {
            return round($milliseconds / 1000, 1).'s';
        }

        return sprintf('%dm %ds', intdiv($milliseconds, 60000), intdiv($milliseconds % 60000, 1000));
    }

    /**
     * 筛选长时间停留在"执行中"的记录
     *
     * 判定依据是超时未落终态，不做自动改写：子进程被杀时父进程仍会记终态，
     * 真正会滞留 running 的是 schedule:run 自身被中断（部署重启、PHP fatal、超时）。
     */
    #[Scope]
    protected function stale(Builder $query): void
    {
        $query->where('status', ScheduleRunStatus::Running)
            ->where('started_at', '<=', now()->subHour());
    }

    /**
     * 获取本次运行的注册表键
     *
     * 含 cron 表达式与节点，避免同签名不同调度、多节点之间互相覆盖。
     *
     * @param  string  $task  任务标识（命令签名）
     * @param  string  $expression  cron 表达式
     */
    public static function runKey(string $task, string $expression): string
    {
        return static::registryPrefix()."run:{$task}:{$expression}";
    }

    /**
     * 获取任务级注册表键
     *
     * 命令侧只能拿到命令签名（拿不到 cron 表达式），故单独维护一个宽键。
     *
     * @param  string  $task  任务标识（命令签名）
     */
    public static function taskKey(string $task): string
    {
        return static::registryPrefix()."task:{$task}";
    }

    /**
     * 登记本次运行的日志 ID
     *
     * @param  string  $task  任务标识（命令签名）
     * @param  string  $expression  cron 表达式
     * @param  int  $logId  日志主键
     */
    public static function rememberRun(string $task, string $expression, int $logId): void
    {
        Cache::put(static::runKey($task, $expression), $logId, self::REGISTRY_TTL);
        Cache::put(static::taskKey($task), $logId, self::REGISTRY_TTL);
    }

    /**
     * 读取本次运行的日志 ID
     *
     * @param  string  $task  任务标识（命令签名）
     * @param  string  $expression  cron 表达式
     *
     * @return int|null 日志主键，未登记时为 null
     */
    public static function runLogId(string $task, string $expression): ?int
    {
        return static::readRegistry(static::runKey($task, $expression));
    }

    /**
     * 更新当前运行记录的业务上下文
     *
     * 仅在注册表存在本次运行且记录仍处于"执行中"时写入：
     * 手动执行命令、注册表过期、执行已结束时静默返回，不建新记录、不抛错。
     *
     * @param  string  $task  任务标识（命令签名）
     * @param  array<string, mixed>  $context  业务摘要，与已有 context 按 key 合并
     */
    public static function updateCurrentContext(string $task, array $context): void
    {
        try {
            if (!$logId = static::readRegistry(static::taskKey($task))) {
                return;
            }

            $log = static::find($logId);

            // 记录必须仍在执行中，否则手动执行命令会污染最近一次调度日志
            if (!$log || !$log->isRunning()) {
                return;
            }

            $log->update(['context' => array_merge($log->context ?? [], $context)]);
        } catch (Throwable $e) {
            // 埋点为"尽力而为"，失败只上报，不能影响命令本身
            report($e);
        }
    }

    /**
     * 获取注册表键前缀
     */
    protected static function registryPrefix(): string
    {
        $server = config('custom.server_id') ?: 'default';

        return "schedule_run_log:{$server}:";
    }

    /**
     * 读取注册表
     *
     * @return int|null 日志主键，未登记时为 null
     */
    protected static function readRegistry(string $key): ?int
    {
        $value = Cache::get($key);

        return $value === null ? null : (int) $value;
    }
}
