<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Services\Mall\OrderActions\Concerns\ReleasesOrderResources;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * 删除订单（软删）
 *
 * 入口：OrderService::delete() / Delete::dispatch()。
 *
 * before：仅待付款、已取消订单可删除
 * handle：待付款订单先回退库存与优惠券，再软删
 * log   ：写日志中心 order.deleted
 * after ：空
 */
final class Delete extends AbstractOperation
{
    use ReleasesOrderResources;

    public function __construct(
        private readonly Order $order,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：订单状态允许删除
     */
    public function before(): void
    {
        if (!in_array($this->order->status, [OrderStatus::Pending, OrderStatus::Canceled], true)) {
            throw new InvalidArgumentException('当前订单状态不允许删除');
        }
    }

    /**
     * 释放资源并软删订单
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            // 待付款订单删除前先释放下单时占用的库存与优惠券，避免资源泄漏
            if ($this->order->status === OrderStatus::Pending) {
                $this->releaseOrderResources($this->order);
            }

            $this->order->delete();

            return true;
        });
    }

    /**
     * 记录删除日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.deleted', '订单删除', $this->operator, $this->order, null, [
            'status' => $this->order->status->value,
        ]);
    }
}
