<?php

namespace App\Services\Mall\RefundActions;

use App\Contracts\Authenticatable;
use App\Contracts\Mall\AbstractOperation;
use App\Contracts\Refundable;
use App\Enums\Mall\OrderStatus;
use App\Enums\Mall\RefundStatus;
use App\Events\Mall\RefundCompleted;
use App\Models\Mall\Order;
use App\Models\Mall\Refund;
use App\Services\Campaign\CouponService;
use App\Services\System\EventLogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * 退款完成（processing → completed）
 *
 * 入口：RefundService::confirmRefund() / Complete::dispatch()；
 * 主要由支付退款成功事件驱动，也可由后台人工确认。
 *
 * before：只有退款处理中的退款单可以完成
 * handle：加锁复核状态、回写退款时间、回收可退款主体资源、推进订单状态、返还优惠券
 * log   ：写日志中心 refund.completed
 * after ：空
 */
final class Complete extends AbstractOperation
{
    public function __construct(
        private readonly Refund $refund,
        private readonly Authenticatable $operator,
        private readonly ?string $remark = null,
    ) {}

    /**
     * 前置：只有退款处理中的退款单可以完成
     */
    public function before(): void
    {
        if ($this->refund->status !== RefundStatus::Processing) {
            throw new RuntimeException('只能确认退款处理中的退款单');
        }
    }

    /**
     * 退款完成落库与资源回收
     *
     * @throws Throwable
     */
    public function handle(): bool
    {
        return DB::transaction(function (): bool {
            // 加行锁后重读，避免支付回调与人工确认并发重复完成
            Refund::whereKey($this->refund->getKey())->lockForUpdate()->first();
            $this->refund->refresh();

            if ($this->refund->status !== RefundStatus::Processing) {
                throw new RuntimeException('只能确认退款处理中的退款单');
            }

            $this->refund->update([
                'status' => RefundStatus::Completed,
                'refund_at' => now(),
            ]);

            // 退款资源回收，委托给可订购主体的 Refundable 实现
            $this->refund->loadMissing('items.orderItem.orderable');
            foreach ($this->refund->items as $refundItem) {
                $orderable = $refundItem->orderItem?->orderable;
                if ($orderable instanceof Refundable) {
                    $orderable->refund($refundItem, $refundItem->qty);
                }
            }

            // 检查是否全部商品已退款，更新订单状态
            // 锁订单串行化「最后一笔退款」的判定，避免多笔退款并发完成时都看不到对方而漏置 Refunded
            $order = Order::whereKey($this->refund->order_id)->lockForUpdate()->firstOrFail();
            $order->loadMissing('items');
            $allRefunded = $this->allItemsRefunded($order);

            $this->updateOrderStatusAfterRefund($order, $allRefunded);

            // 全部退款完成：返还订单占用的优惠券（部分退款不返还，券作用于整单基数）
            // 退款已按实付口径分摊并执行，故保留订单 coupon_discount 历史快照，不归零
            if ($allRefunded) {
                service(CouponService::class)->releaseFromRefundedOrder($order);
            }

            RefundCompleted::dispatch($this->refund, $this->operator);

            return true;
        });
    }

    /**
     * 记录退款完成日志
     *
     * @param  mixed  $result  handle() 的返回值
     */
    public function log(mixed $result): void
    {
        service(EventLogService::class)->write(
            'refund.completed',
            '退款完成',
            $this->operator,
            $this->refund,
            $this->remark,
            [
                'previous_status' => RefundStatus::Processing->value,
                'next_status' => RefundStatus::Completed->value,
                'refund_at' => $this->refund->refund_at?->toDateTimeString(),
            ]
        );
    }

    /**
     * 判断订单商品是否已全部退款
     *
     * @param  Order  $order  订单
     *
     * @return bool 全部订单项数量均已退完
     */
    private function allItemsRefunded(Order $order): bool
    {
        $order->loadMissing('items');

        $completedRefunds = $order->refunds()
            ->where('status', RefundStatus::Completed)
            ->with('items')
            ->get();

        foreach ($order->items as $orderItem) {
            $refundedQty = 0;
            foreach ($completedRefunds as $refund) {
                foreach ($refund->items as $refundItem) {
                    if ($refundItem->order_item_id === $orderItem->id) {
                        $refundedQty += $refundItem->qty;
                    }
                }
            }

            if ($refundedQty < $orderItem->qty) {
                return false;
            }
        }

        return true;
    }

    /**
     * 退款完成后更新订单状态
     *
     * 全部退款才进入 Refunded 终态；部分退款保留原订单状态，待剩余商品继续履约。
     *
     * @param  Order  $order  订单
     * @param  bool  $allRefunded  是否全部商品已退款
     */
    private function updateOrderStatusAfterRefund(Order $order, bool $allRefunded): void
    {
        if (!$allRefunded || $order->status === OrderStatus::Refunded) {
            return;
        }

        $previousStatus = $order->status;

        $order->update(['status' => OrderStatus::Refunded]);

        // 订单时间线保留全额退款记录
        service(EventLogService::class)->write('order.refunded', '订单退款', $this->operator, $order, null, [
            'previous_status' => $previousStatus->value,
            'next_status' => OrderStatus::Refunded->value,
        ]);
    }
}
