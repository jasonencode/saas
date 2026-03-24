<?php

namespace App\Contracts;

use App\Models\PaymentOrder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface ShouldPayment
{
    /**
     * 支付订单关联
     *
     * @return MorphMany
     */
    public function paymentOrders(): MorphMany;

    /**
     * 获取模型的支付标题
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * 获取支付额
     *
     * @return string
     */
    public function getTotalAmount(): string;

    /**
     * 支付完成的回调
     *
     * @param  PaymentOrder  $order
     * @return bool
     */
    public function paid(PaymentOrder $order): bool;

    /**
     * 该订单是否可以支付
     *
     * @return bool
     */
    public function canPay(): bool;

    /**
     * 是否可退款
     *
     * @return bool
     */
    public function canRefund(): bool;
}
