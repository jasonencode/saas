<?php

namespace App\Services\Mall\RefundActions\Concerns;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;

/**
 * 判断退款是否需要买家寄回商品
 *
 * 未发货（已支付、备货中）的订单无需退货；其余状态按退货退款处理。
 */
trait DeterminesRefundReturn
{
    /**
     * 判断退款是否需要退货
     *
     * @param  Order  $order  订单
     */
    protected function needsReturn(Order $order): bool
    {
        return !in_array($order->status, [OrderStatus::Paid, OrderStatus::Preparing], true);
    }
}
