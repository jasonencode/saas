<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Events\Mall\OrderCanceled;
use App\Models\Mall\Order;
use App\Services\Mall\OrderActions\Concerns\AssertsOrderState;
use App\Services\Mall\OrderActions\Concerns\ReleasesOrderResources;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 取消订单（pending → canceled）
 *
 * 入口：OrderService::cancel() / Cancel::dispatch()；
 * 用户主动取消、超时关闭任务（AutoCloseOrder）共用。
 *
 * before：状态预校验（handle 内加锁复核）
 * handle：回退下单占用的库存与优惠券、状态落库并派发 OrderCanceled
 * log   ：写日志中心 order.canceled
 * after ：空
 */
final class Cancel extends AbstractOperation
{
    use AssertsOrderState,
        ReleasesOrderResources;

    /**
     * 取消前的状态（供日志记录）
     */
    private ?OrderStatus $previousStatus = null;

    public function __construct(
        private readonly Order $order,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：状态预校验
     */
    public function before(): void
    {
        $this->assertCan($this->order, OrderStatus::Canceled);
    }

    /**
     * 回退资源并落库取消状态
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            $this->assertCanWithLock($this->order, OrderStatus::Canceled);

            $this->previousStatus = $this->order->status;

            // 未支付订单取消：回退下单时占用的库存并释放已核销的优惠券
            $this->releaseOrderResources($this->order);

            $this->order->status = OrderStatus::Canceled;
            $this->order->save();

            OrderCanceled::dispatch($this->order, $this->operator);

            return true;
        });
    }

    /**
     * 记录取消日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.canceled', '订单取消', $this->operator, $this->order, null, [
            'previous_status' => $this->previousStatus?->value,
            'status' => $this->order->status->value,
        ]);
    }
}
