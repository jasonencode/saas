<?php

namespace App\Services\Mall\OrderActions\Concerns;

use App\Enums\Mall\OrderStatus;
use App\Models\Mall\Order;
use RuntimeException;

/**
 * 订单状态流转校验
 *
 * 并发场景下（重复提交、支付回调重试、自动任务与人工操作同时触发），
 * 先前的「事务外读取状态」会导致多个请求同时通过校验并重复流转、
 * 重复派发下游事件。因此在事务内对订单行加锁并重读，确保串行判定。
 */
trait AssertsOrderState
{
    /**
     * 校验状态流转（不处理并发）
     *
     * @param  Order  $order  订单
     * @param  OrderStatus  $transition  目标状态
     *
     * @throws RuntimeException 订单状态不允许流转
     */
    protected function assertCan(Order $order, OrderStatus $transition): void
    {
        // 传入订单履约方式，使 Paid 状态按履约方式分流（mail/preparing、pickup/自提、virtual/直连完成）
        if ($order->status->canTransitionTo($transition, $order->fulfillment_type)) {
            return;
        }

        throw new RuntimeException(
            "订单状态不可从「{$order->status->getLabel()}」变更为「{$transition->getLabel()}」",
        );
    }

    /**
     * 在事务内加行锁并校验状态流转
     *
     * @param  Order  $order  订单
     * @param  OrderStatus  $transition  目标状态
     *
     * @throws RuntimeException 订单状态不允许流转
     */
    protected function assertCanWithLock(Order $order, OrderStatus $transition): void
    {
        Order::whereKey($order->getKey())->lockForUpdate()->first();

        $order->refresh();

        $this->assertCan($order, $transition);
    }
}
