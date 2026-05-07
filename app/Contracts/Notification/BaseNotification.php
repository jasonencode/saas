<?php

namespace App\Contracts\Notification;

use App\Contracts\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * 基础通知类
 */
abstract class BaseNotification extends Notification implements ShouldQueue
{
    /**
     * 队列连接
     */
    public string $connection = 'redis';

    /**
     * 尝试次数
     */
    public int $tries = 3;

    /**
     * 重试间隔（秒）
     */
    public array $backoff = [10, 60, 300];

    /**
     * 发送通道
     */
    abstract public function via(Authenticatable $user): array;

    /**
     * 用这个方法来做消息通知的分组？？
     */
    public function databaseType(Authenticatable $user): string
    {
        return 'default';
    }
}
