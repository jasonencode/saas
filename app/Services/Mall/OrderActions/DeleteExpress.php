<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\Mall\OrderShipping;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * 删除发货记录
 *
 * 入口：OrderService::deleteExpress() / DeleteExpress::dispatch()。
 *
 * before：空（发货记录由调用方加载）
 * handle：事务内删除运单与明细回写，并按剩余发货情况回退订单状态
 * log   ：写日志中心 order.express_deleted
 * after ：空
 */
final class DeleteExpress extends AbstractOperation
{
    public function __construct(
        private readonly OrderShipping $express,
        private readonly Authenticatable $operator,
    ) {}

    /**
     * 前置：空
     */
    public function before(): void {}

    /**
     * 删除运单并回退订单状态
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            $order = $this->express->order;

            Order::whereKey($order->getKey())->lockForUpdate()->first();
            $order->refresh();

            $itemsToReset = $order
                ->items()
                ->where('order_shipping_id', $this->express->id)
                ->get();

            $this->express->delete();

            if ($itemsToReset->isNotEmpty()) {
                $order
                    ->items()
                    ->whereIn('id', $itemsToReset->pluck('id'))
                    ->update(['order_shipping_id' => null]);
            }

            $totalItems = $order->items()->count();
            $shippedItems = $order->items()->whereNotNull('order_shipping_id')->count();

            if ($shippedItems === 0) {
                if (in_array($order->status, [OrderStatus::Delivered, OrderStatus::PartiallyShipped], true)) {
                    $order->status = OrderStatus::Paid;
                }
            } elseif ($shippedItems < $totalItems) {
                $order->status = OrderStatus::PartiallyShipped;
            } else {
                $order->status = OrderStatus::Delivered;
            }

            $order->save();

            return true;
        });
    }

    /**
     * 记录删除发货日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write('order.express_deleted', '删除发货记录', $this->operator, $this->express->order, null, [
            'express_id' => $this->express->getKey(),
            'express_no' => $this->express->express_no,
        ]);
    }
}
