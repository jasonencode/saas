<?php

namespace App\Contracts;

use Closure;

/**
 * 管道任务，步骤必须实现的接口
 */
interface PipelineStep
{
    /**
     * 任务处理逻辑
     *
     * @param  mixed    $passable  要处理的数据
     * @param  Closure  $next      传递到下一个步骤的方法
     * @return mixed
     */
    public function handle(mixed $passable, Closure $next): mixed;
}
