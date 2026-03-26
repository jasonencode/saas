<?php

namespace App\Contracts;

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
     * 获取支付标题
     *
     * @return string
     */
    public function getTitleAttribute(): string;

    /**
     * 获取支付额
     *
     * @return float
     */
    public function getTotalAmount(): float;
}
