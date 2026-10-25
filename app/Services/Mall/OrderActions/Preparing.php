<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Events\Mall\OrderPreparing;
use App\Models\Mall\Order;
use App\Services\Mall\OrderActions\Concerns\AssertsOrderState;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 订单备货中（paid → preparing）
 *
 * 入口：OrderService::preparing() / Preparing::dispatch()。
 *
 * before：状态预校验（handle 内加锁复核）
 * handle：状态落库并派发 OrderPreparing
 * log   ：写日志中心 order.delivering
 * after ：空
 */
final class Preparing extends AbstractOperation
{
    use AssertsOrderState;

    public function __construct(
        private readonly Order $order,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：只有已支付订单可以备货
     */
    public function before(): void
    {
        $this->assertCan($this->order, OrderStatus::Preparing);
    }

    /**
     * 状态落库
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            $this->assertCanWithLock($this->order, OrderStatus::Preparing);

            $this->order->status = OrderStatus::Preparing;
            $this->order->save();

            OrderPreparing::dispatch($this->order, $this->operator);

            return true;
        });
    }

    /**
     * 记录备货日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.delivering', '订单备货', $this->operator, $this->order);
    }
}
