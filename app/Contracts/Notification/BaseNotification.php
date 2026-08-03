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
     * 获取发送通道
     *
     * @param  Authenticatable  $user  通知用户
     *
     * @return array 发送通道列表
     */
    abstract public function via(Authenticatable $user): array;

    /**
     * 获取数据库通知类型
     *
     * @param  Authenticatable  $user  通知用户
     *
     * @return string 通知类型
     */
    public function databaseType(Authenticatable $user): string
    {
        return 'default';
    }
}
