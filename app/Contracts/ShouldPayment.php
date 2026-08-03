<?php

namespace App\Contracts;

use App\Models\Finance\PaymentOrder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * 可支付模型接口
 */
interface ShouldPayment
{
    /**
     * 关联支付单
     *
     * @return MorphMany<PaymentOrder>
     */
    public function paymentOrders(): MorphMany;

    /**
     * 获取支付标题
     */
    public function getTitleAttribute(): string;

    /**
     * 获取支付金额
     *
     * @return string 支付金额
     */
    public function getTotalAmount(): string;
}
