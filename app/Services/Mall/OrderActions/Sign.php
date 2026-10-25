<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Events\Mall\OrderSigned;
use App\Models\Mall\Order;
use App\Services\Mall\OrderActions\Concerns\AssertsOrderState;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 订单签收（delivered / partially → signed）
 *
 * 入口：OrderService::sign() / Sign::dispatch()；用户手动签收与 AutoSignOrder 任务共用。
 *
 * before：状态预校验（handle 内加锁复核）
 * handle：回写签收时间、全部运单签收、状态落库并派发 OrderSigned
 * log   ：写日志中心 order.signed
 * after ：空
 */
final class Sign extends AbstractOperation
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
        $this->assertCan($this->order, OrderStatus::Signed);
    }

    /**
     * 回写签收时间并落库状态
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            $this->assertCanWithLock($this->order, OrderStatus::Signed);

            $this->order->status = OrderStatus::Signed;
            $this->order->signed_at = now();
            $this->order->save();

            $this->order->shippings()
                ->whereNull('sign_at')
                ->update(['sign_at' => now()]);

            OrderSigned::dispatch($this->order, $this->operator);

            return true;
        });
    }

    /**
     * 记录签收日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.signed', '订单签收', $this->operator, $this->order, null, [
            'signed_at' => $this->order->signed_at?->toDateTimeString(),
        ]);
    }
}
