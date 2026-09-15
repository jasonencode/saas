<?php

namespace App\Support\ScheduledTask;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Str;

/**
 * 计划任务标识
 *
 * 统一负责"注册了哪些计划任务"与"任务的签名是什么"两件事：
 * 执行日志（调度链路）与命令行留痕（手动执行）共用同一份解析结果，
 * 不再维护第二份任务清单——新增或删除 routes/console.php 里的任务会自动同步。
 */
class ScheduledTask
{
    /**
     * 解析调度事件的命令签名
     *
     * 项目内的计划任务均以字符串签名注册（Schedule::command('app:xxx')），
     * 此时 $event->command 形如 `php artisan app:xxx --flag=1` 且 description 为 null，
     * 只能取命令串中 artisan 之后的第一段，与命令内 $this->getName() 保持一致。
     */
    public static function name(Event $task): string
    {
        if ($task instanceof CallbackEvent) {
            return 'callback:'.substr(sha1((string) $task->getSummaryForDisplay()), 0, 12);
        }

        return static::parse((string) $task->command);
    }

    /**
     * 解析命令串中的命令签名
     *
     * 注意 Windows 下 php 与 artisan 两段都可能被引号包裹：
     * `"D:\php\php.exe" "artisan" app:xxx`。
     *
     * @param  string  $command  完整命令串
     */
    public static function parse(string $command): string
    {
        // 尝试匹配 artisan 后面的命令签名
        if (preg_match('/artisan(?:\.php)?\s+(\S+)/', $command, $matches) === 1) {
            return $matches[1];
        }

        // 兜底：去掉引号后取第一段
        return (string) Str::of($command)->trim('"\' ')->before(' ')->trim();
    }

    /**
     * 获取已注册的计划任务签名
     *
     * 直接读调度器里真实注册的结果，而不是维护一张手工白名单。
     * 非 console 上下文（未加载 routes/console.php）时返回空数组。
     *
     * @return array<int, string>
     */
    public static function registered(): array
    {
        return array_values(array_unique(array_map(
            static fn (Event $task): string => static::name($task),
            app(Schedule::class)->events()
        )));
    }

    /**
     * 是否为已注册的计划任务
     *
     * @param  string  $task  命令签名
     */
    public static function isRegistered(string $task): bool
    {
        return in_array($task, static::registered(), true);
    }

    /**
     * 获取所有已注册任务的原始命令字符串（调试用）
     *
     * @return array<int, string>
     */
    public static function rawCommands(): array
    {
        return array_values(array_map(
            static fn (Event $task): string => (string) $task->command,
            app(Schedule::class)->events()
        ));
    }
}
