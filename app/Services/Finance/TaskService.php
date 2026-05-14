<?php

namespace App\Services\Finance;

use App\Contracts\ServiceInterface;
use App\Contracts\SettlementTask;
use App\Models\Finance\Task;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;

class TaskService implements ServiceInterface
{
    /**
     * 已注册的任务类 => 标题
     *
     * @var array<string, string>
     */
    protected static array $tasks = [];

    /**
     * 注册任务
     *
     * @param  class-string<SettlementTask>  $taskClass
     * @throws BindingResolutionException
     */
    public static function register(string $taskClass): void
    {
        if (!class_exists($taskClass)) {
            throw new InvalidArgumentException("任务类 [{$taskClass}] 不存在");
        }

        if (!is_subclass_of($taskClass, SettlementTask::class)) {
            throw new InvalidArgumentException("任务类 [{$taskClass}] 未实现 SettlementTask 接口");
        }

        self::$tasks[$taskClass] = app()->make($taskClass, ['task' => new Task()])->getTitle();
    }

    /**
     * 从数据库自动注册所有任务
     */
    public static function registerFromDatabase(): void
    {
        Task::ofEnabled()
            ->distinct()
            ->pluck('service')
            ->filter(fn (string $service) => ! isset(self::$tasks[$service]))
            ->each(fn (string $service) => rescue(fn () => static::register($service)));
    }

    /**
     * 解析任务实例（通过容器）
     *
     * @param  class-string<SettlementTask>  $taskClass
     * @param  Task  $task
     * @return SettlementTask
     * @throws BindingResolutionException
     */
    public static function resolve(string $taskClass, Task $task): SettlementTask
    {
        if (!isset(self::$tasks[$taskClass])) {
            static::register($taskClass);
        }

        return app()->make($taskClass, ['task' => $task]);
    }

    /**
     * 获取任务列表
     *
     * @return array<string, string>
     */
    public static function list(): array
    {
        $tasks = self::$tasks;
        asort($tasks);

        return $tasks;
    }
}
