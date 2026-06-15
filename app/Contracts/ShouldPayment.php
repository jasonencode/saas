<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface ShouldPayment
{
    /**
     * 支付单关联
     */
    public function paymentOrders(): MorphMany;

    /**
     * 获取支付标题
     */
    public function getTitleAttribute(): string;

    /**
     * 获取支付金额
     */
    public function getTotalAmount(): string;
}
