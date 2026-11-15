<?php

namespace App\Services\Mall\OrderActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use App\Models\Mall\OrderItem;
use App\Models\Mall\OrderShipping;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
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

            // 签收后退款/完成订单不允许再篡改发货记录，否则会留下「已签收但未发货」的矛盾数据
            if (!in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing, OrderStatus::PartiallyShipped, OrderStatus::Delivered], true)) {
                throw new RuntimeException('当前订单状态不允许删除发货记录');
            }

            $order->items()
                ->where('order_shipping_id', $this->express->id)
                ->update(['order_shipping_id' => null, 'shipped_qty' => 0]);

            $this->express->delete();

            $items = $order->items()
                ->with('refundItems.refund')
                ->get();

            $shippedQty = (int) $items->sum(fn (OrderItem $item): int => $item->resolveShippedQty());
            $hasPending = $items->contains(
                fn (OrderItem $item): bool => $item->getShippableQtyAttribute() > 0
            );

            if ($shippedQty === 0) {
                if (in_array($order->status, [OrderStatus::Delivered, OrderStatus::PartiallyShipped], true)) {
                    $order->status = OrderStatus::Paid;
                }

                // 没有发货记录时不能再保留自动签收时间
                $order->auto_sign_at = null;
            } elseif ($hasPending) {
                $order->status = OrderStatus::PartiallyShipped;
                $order->auto_sign_at = null;
            } else {
                $order->status = OrderStatus::Delivered;
                $order->auto_sign_at ??= now()->addDays((int) config('custom.mall.auto_sign_days', 7));
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
