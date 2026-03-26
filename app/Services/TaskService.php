<?php

namespace App\Services;

use App\Contracts\ServiceInterface;
use App\Contracts\SettlementTask;
use InvalidArgumentException;

class TaskService implements ServiceInterface
{
    public static array $tasks = [];

    /**
     * 注册任务
     *
     * @param  string<SettlementTask>  $task
     * @return void
     */
    public static function register(string $task): void
    {
        if (!class_exists($task)) {
            throw new InvalidArgumentException('任务类不存在');
        }

        if (!is_subclass_of($task, SettlementTask::class)) {
            throw new InvalidArgumentException('任务类未实现 SettlementTask 接口');
        }

        self::$tasks[$task] = resolve($task)->getTitle();
    }

    /**
     * 获取任务列表
     *
     * @return array
     */
    public static function list(): array
    {
        $tasks = self::$tasks;
        asort($tasks);

        return $tasks;
    }
}
