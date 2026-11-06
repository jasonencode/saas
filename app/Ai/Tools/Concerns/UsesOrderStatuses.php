<?php

namespace App\Ai\Tools\Concerns;

use App\Enums\Mall\OrderStatus;

/**
 * 订单统计口径的公共状态集合
 */
trait UsesOrderStatuses
{
    /**
     * 已支付及之后的订单状态（排除待付款与已取消）
     *
     * @return array<int, string>
     */
    protected function salesStatuses(): array
    {
        return array_values(array_map(
            fn (OrderStatus $status) => $status->value,
            array_filter(
                OrderStatus::cases(),
                fn (OrderStatus $status) => !in_array($status, [OrderStatus::Pending, OrderStatus::Canceled], true),
            ),
        ));
    }
}
