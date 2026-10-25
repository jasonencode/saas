<?php

namespace App\Exceptions\Mall;

use App\Models\Mall\Order;
use RuntimeException;

/**
 * 订单当前状态不可支付
 *
 * 典型场景：用户已在支付渠道完成付款，但订单已被超时任务关闭。
 * 调用方（支付回调）应据此发起原路退款，而不是持续返回失败。
 */
class OrderNotPayableException extends RuntimeException
{
    public function __construct(public readonly Order $order, string $message = '订单状态不可支付')
    {
        parent::__construct($message);
    }
}
