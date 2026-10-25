<?php

namespace App\Contracts\Mall;

use Illuminate\Support\Facades\DB;

/**
 * 商城操作的抽象基类
 *
 * 统一实现 OperationInterface 并提供同步 dispatch() 入口：子类只需实现 before() 与 handle()，
 * 需要写日志或副作用时再覆盖 log() / after()。
 *
 * 用法：
 *   Pay::dispatch($order, $user);
 *   Deliver::dispatch($order, $itemIds, $expressId, $expressNo, $user);
 *   Deliver::dispatch(order: $order, itemIds: [1], expressId: 1, expressNo: 'SF123', user: $user);
 *
 * 事务约定：
 *   - 事务边界由 handle() 自己负责（动作内部 DB::transaction），基类不额外开事务；
 *   - log() 在 handle() 返回后立即执行，处于动作事务之外；
 *   - after() 通过 DB::afterCommit() 执行：调用方若已包在外层事务中，副作用延后到提交之后，
 *     避免回滚后仍发出通知 / 派发队列任务。
 */
abstract class AbstractOperation implements OperationInterface
{
    /**
     * 同步执行 Operation
     *
     * before() 校验不通过会直接抛 RuntimeException；
     * handle() 返回 false 时跳过 log() 与 after()。
     *
     * @param  mixed  ...$arguments  透传给构造函数（支持位置参数与命名参数）
     */
    final public static function dispatch(mixed ...$arguments): mixed
    {
        /** @var OperationInterface $action */
        $action = new static(...$arguments);

        $action->before();

        $result = $action->handle();

        if ($result === false) {
            return false;
        }

        $action->log($result);

        // 无活动事务时立即执行；有外层事务时登记为提交后回调
        DB::afterCommit(static fn () => $action->after());

        return $result;
    }

    /**
     * 记录本次操作日志（默认不写，需要审计的动作覆盖此方法）
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void {}

    /**
     * 后置副作用（默认无，需要通知 / 事件 / 队列的动作覆盖此方法）
     */
    public function after(): void {}
}
