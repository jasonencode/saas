<?php

namespace App\Events\Finance;

use App\Models\Finance\PaymentRefund;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 支付退款完成事件
 *
 * 支付侧原路退回成功后派发，商城售后单等来源单据据此完成退款。
 */
class PaymentRefundCompleted
{
    use Dispatchable,
        SerializesModels;

    public function __construct(
        public PaymentRefund $refund,
    ) {}
}
