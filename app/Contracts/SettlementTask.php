<?php

namespace App\Contracts;

use Closure;

/**
 * 结算任务接口
 */
interface SettlementTask
{
    /**
     * 获取默认选项
     *
     * @return array 默认选项
     */
    public function getDefaultOptions(): array;

    /**
     * 获取任务标题
     *
     * @return string 任务标题
     */
    public function getTitle(): string;

    /**
     * 获取任务描述
     *
     * @return string 任务描述
     */
    public function getDescription(): string;

    /**
     * 处理结算任务
     *
     * @param  SettleTaskData  $data  结算任务数据
     * @param  Closure  $next  下一个任务回调
     *
     * @return mixed 处理结果
     */
    public function handle(SettleTaskData $data, Closure $next): mixed;
}
