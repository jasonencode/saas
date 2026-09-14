<?php

namespace App\Jobs;

use App\Contracts\Authenticatable;
use App\Models\System\System;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 基础队列任务类
 *
 * 所有队列任务的抽象基类，提供通用的队列配置和辅助方法。
 * 子类必须实现 handle() 方法定义具体任务逻辑。
 *
 * @module 通用
 */
abstract class BaseJob implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        SerializesModels;

    /** @var string 队列连接驱动 */
    public string $connection = 'redis';

    /** @var string 队列名称 */
    public string $queue = 'default';

    /** @var DateTimeInterface|DateInterval|array|int|null 任务延迟执行时间 */
    public DateTimeInterface|DateInterval|array|int|null $delay = 0;

    /** @var int 任务超时时间（秒） */
    public int $timeout = 30;

    /** @var int 最大尝试次数 */
    public int $tries = 1;

    /**
     * 执行任务的核心逻辑
     */
    abstract public function handle(): void;

    /**
     * 设置任务延迟执行时间
     *
     * @param  DateTimeInterface|DateInterval|array|int|null  $delay  延迟时间
     */
    public function delay(DateTimeInterface|DateInterval|array|int|null $delay = null): self
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * 获取任务失败后的退避时间策略
     *
     * @return array<int, int> 退避时间数组（秒）
     */
    public function backoff(): array
    {
        return [5, 10, 30];
    }

    /**
     * 获取当前操作的系统用户（队列）
     *
     * @see database/seeders/SystemTableSeeder ID 2 = 队列
     */
    protected function user(): Authenticatable
    {
        return System::find(2);
    }
}
