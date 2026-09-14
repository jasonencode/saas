<?php

namespace App\Console\Commands;

use App\Contracts\Authenticatable;
use App\Models\System\System;
use Illuminate\Console\Command;

/**
 * 基础命令类
 *
 * 所有 Artisan 命令的抽象基类，提供通用的辅助方法。
 * 子类必须实现 handle() 方法定义具体命令逻辑。
 */
abstract class BaseCommand extends Command
{
    /**
     * 获取当前操作的系统用户（命令行）
     *
     * @see database/seeders/SystemTableSeeder ID 3 = 计划任务
     */
    protected function user(): Authenticatable
    {
        return System::find(3);
    }
}
