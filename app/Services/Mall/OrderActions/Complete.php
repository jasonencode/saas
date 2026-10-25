<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Events\Mall\OrderCompleted;
use App\Models\Mall\Order;
use App\Services\Mall\OrderActions\Concerns\AssertsOrderState;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 订单完成（signed / verified / paid-virtual → completed）
 *
 * 入口：OrderService::complete() / Complete::dispatch()；
 * 主要由 OrderAutoCompleteCommand 定时任务调用，也可由后台手动完成。
 *
 * before：状态预校验（handle 内加锁复核）
 * handle：状态落库并派发 OrderCompleted
 * log   ：写日志中心 order.completed
 * after ：空
 */
final class Complete extends AbstractOperation
{
    use AssertsOrderState;

    public function __construct(
        private readonly Order $order,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：状态预校验
     */
    public function before(): void
    {
        $this->assertCan($this->order, OrderStatus::Completed);
    }

    /**
     * 状态落库
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            $this->assertCanWithLock($this->order, OrderStatus::Completed);

            $this->order->status = OrderStatus::Completed;
            $this->order->save();

            OrderCompleted::dispatch($this->order, $this->operator);

            return true;
        });
    }

    /**
     * 记录完成日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.completed', '订单完成', $this->operator, $this->order);
    }
}
