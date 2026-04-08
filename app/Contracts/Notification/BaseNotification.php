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
     * 队列名称
     */
    public string $queue = 'notifications';

    /**
     * 延迟时间（秒）
     */
    public int $delay = 0;

    /**
     * 尝试次数
     */
    public int $tries = 3;

    /**
     * 重试间隔（秒）
     */
    public int $backoff = 60;

    /**
     * 获取通知分组标题
     */
    abstract public static function getGroupTitle(): string;

    /**
     * 获取通知类型
     */
    abstract public static function getType(): string;

    /**
     * 获取通知图标
     */
    public function getIcon(): string
    {
        return 'bell';
    }

    /**
     * 获取通知颜色
     */
    public function getColor(): string
    {
        return 'primary';
    }

    /**
     * 发送通道
     */
    public function via(Authenticatable $user): array
    {
        return ['database'];
    }

    /**
     * 数据库通知数据
     */
    public function toArray(Authenticatable $notifiable): array
    {
        return [
            'type' => static::getType(),
            'message' => $this->getMessage(),
            'data' => $this->getData(),
        ];
    }

    /**
     * 获取通知消息
     */
    abstract public function getMessage(): string;

    /**
     * 获取通知数据
     */
    protected function getData(): array
    {
        return [];
    }

    /**
     * 获取通知链接
     */
    public function getUrl(Authenticatable $notifiable): ?string
    {
        return null;
    }
}
