<?php

namespace App\Models\System;

use App\Contracts\Authenticatable;
use App\Policies\System\SystemPolicy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

/**
 * 系统账号模型
 *
 * 内置固定账号，供队列任务、计划任务等无登录上下文的场景作为操作人（operator）使用。
 *
 * @see database/seeders/SystemTableSeeder
 */
#[Unguarded]
#[UsePolicy(SystemPolicy::class)]
class System extends Authenticatable
{
    /**
     * 系统账号：系统
     */
    public const int SYSTEM_ID = 1;

    /**
     * 系统账号：队列
     */
    public const int QUEUE_ID = 2;

    /**
     * 系统账号：计划任务
     */
    public const int SCHEDULE_ID = 3;

    /**
     * 获取「系统」账号
     */
    public static function system(): static
    {
        return static::find(self::SYSTEM_ID);
    }

    /**
     * 获取「队列」账号（队列任务操作人）
     */
    public static function queue(): static
    {
        return static::find(self::QUEUE_ID);
    }

    /**
     * 获取「计划任务」账号（命令行 / 调度器操作人）
     */
    public static function schedule(): static
    {
        return static::find(self::SCHEDULE_ID);
    }
}
