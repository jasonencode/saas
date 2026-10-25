<?php

namespace App\Services\Mall\OrderActions\Concerns;

use App\Enums\Mall\DeductStockType;
use App\Models\Mall\Order;
use App\Services\Campaign\CouponService;

/**
 * 订单资源释放
 *
 * 取消 / 删除待付款订单时回退下单占用的库存与优惠券。
 */
trait ReleasesOrderResources
{
    /**
     * 释放订单占用的库存与优惠券
     *
     * 仅在下单时已扣减库存（DeductStockType::Ordered）的订单项上回退库存；
     * 优惠券释放为幂等操作（无核销记录时 no-op）。
     *
     * @param  Order  $order  订单
     */
    protected function releaseOrderResources(Order $order): void
    {
        $order->loadMissing('items.orderable');

        foreach ($order->items as $item) {
            $orderable = $item->orderable;
            if ($orderable && $orderable->getDeductStockType() === DeductStockType::Ordered) {
                $orderable->restoreStock($item->qty);
            }
        }

        service(CouponService::class)->releaseFromOrder($order);
    }
}
