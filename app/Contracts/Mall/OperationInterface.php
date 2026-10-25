<?php

namespace App\Contracts\Mall;

/**
 * 商城操作（Operation）的统一约定，订单与退款等业务动作共用
 *
 * 每个操作拆成四段，由 App\Contracts\Mall\AbstractOperation::dispatch() 依次调用：
 *   before()  前置校验：不满足条件直接抛 RuntimeException
 *   handle()  主逻辑：状态变更与落库，返回 false 表示未执行成功（跳过 log/after）
 *   log()     记录本次操作日志，$result 为 handle() 的返回值
 *   after()   副作用：事件、通知、队列任务等
 *
 * 事务约定（实现类必须遵守）：
 *   1. 事务边界由 handle() 自己负责；before() 不在事务内，只做不依赖并发安全的校验，
 *      数量、余额、状态这类「读后写」的判定必须在 handle() 的事务 + 锁内复核。
 *   2. log() 在 handle() 之后执行，日志失败不回滚主状态。
 *   3. after() 由 dispatch() 通过 DB::afterCommit() 执行：调用方包在外层事务中时，
 *      副作用延后到提交之后，回滚则不执行。
 */
interface OperationInterface
{
    /**
     * 同步执行操作
     *
     * 由 App\Contracts\Mall\AbstractOperation 统一实现（before → handle → log → after）。
     *
     * @param  mixed  ...$arguments  透传给构造函数
     */
    public static function dispatch(mixed ...$arguments): mixed;

    /**
     * 前置校验
     *
     * @throws \RuntimeException 校验不通过时
     */
    public function before(): void;

    /**
     * 主逻辑：状态变更与落库
     *
     * @return mixed false 表示执行失败，其余作为 dispatch 的返回值
     */
    public function handle(): mixed;

    /**
     * 记录本次操作日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void;

    /**
     * 后置：副作用
     */
    public function after(): void;
}
