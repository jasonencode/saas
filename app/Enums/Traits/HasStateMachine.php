<?php

namespace App\Enums\Traits;

/**
 * 状态机 trait
 *
 * 提供 `canTransitionTo()` 方法，基于枚举自身定义的 `next()` 方法判断状态流转合法性。
 * 各枚举需自行定义 `previous()` 和 `next()` 方法。
 */
trait HasStateMachine
{
    /**
     * 判断当前状态是否可以转换到目标状态
     *
     * @param  static  $target  目标状态
     * @param  mixed  ...$args  传递给 next() 的额外参数（如 RefundType）
     */
    public function canTransitionTo(self $target, mixed ...$args): bool
    {
        return in_array($target, $this->next(...$args), true);
    }
}
