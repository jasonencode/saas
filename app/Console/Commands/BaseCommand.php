<?php

namespace App\Console\Commands;

use App\Contracts\Attributes\CommandLabel;
use App\Contracts\Authenticatable;
use App\Models\System\ScheduleRunLog;
use App\Models\System\System;
use Illuminate\Console\Command;
use ReflectionClass;

/**
 * 基础命令类
 *
 * 所有 Artisan 命令的抽象基类，提供通用的辅助方法。
 * 子类必须实现 handle() 方法定义具体命令逻辑，
 * 并通过 #[CommandLabel('中文名称')] 特性声明命令名称。
 */
abstract class BaseCommand extends Command
{
    /**
     * 获取命令的中文名称
     *
     * 优先读取 #[CommandLabel] 特性，未标注时回退到命令签名。
     */
    public function getCommandLabel(): string
    {
        $attributes = (new ReflectionClass($this))->getAttributes(CommandLabel::class);

        if ($attribute = $attributes[0] ?? null) {
            return $attribute->newInstance()->getLabel();
        }

        return (string) $this->getName();
    }

    /**
     * 获取当前操作的系统用户（命令行）
     *
     * @see database/seeders/SystemTableSeeder ID 3 = 计划任务
     */
    protected function user(): Authenticatable
    {
        return System::find(3);
    }

    /**
     * 记录业务上下文到计划任务执行日志
     *
     * 仅在"调度器触发且本次执行仍在进行中"时生效：手动执行命令、
     * 本次执行已结束或运行注册表已过期时自动 no-op，不影响命令本身。
     *
     * @param  array<string, mixed>  $context  业务摘要（如 ['completed' => 37, 'failed' => 0]）
     */
    protected function logContext(array $context): void
    {
        if (!$name = $this->getName()) {
            return;
        }

        ScheduleRunLog::updateCurrentContext($name, $context);
    }
}
