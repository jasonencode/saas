<?php

namespace App\Jobs;

use App\Contracts\Authenticatable;
use App\Models\System\System;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Connection;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;
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
#[Connection('redis')]
#[Queue('default')]
#[Timeout(30)]
#[Tries(1)]
#[Backoff(5, 10, 30)]
abstract class BaseJob implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        SerializesModels;

    /**
     * 执行任务的核心逻辑
     */
    abstract public function handle(): void;

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
