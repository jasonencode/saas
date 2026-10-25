<?php

namespace App\Listeners\Mall;

use App\Enums\Mall\RefundStatus;
use App\Events\Finance\PaymentRefundCompleted;
use App\Models\Mall\Refund;
use App\Models\System\System;
use App\Services\Mall\RefundService;
use Throwable;

/**
 * 支付退款成功后完成商城售后单
 *
 * 支付侧原路退回成功 → 售后单从「退款处理中」流转到「退款完成」，
 * 由 RefundActions\Complete 回收库存/权益并推进订单状态。
 */
class CompleteRefundOnPaymentRefundCompleted
{
    public function handle(PaymentRefundCompleted $event): void
    {
        $refund = $event->refund->source;

        if (!$refund instanceof Refund || $refund->status !== RefundStatus::Processing) {
            return;
        }

        try {
            service(RefundService::class)->confirmRefund(
                refund: $refund,
                user: System::query()->find(System::SYSTEM_ID) ?? $refund->user,
                remark: '支付退款成功',
            );
        } catch (Throwable $e) {
            // 资金已退回，售后单完成失败需上报人工处理，不影响支付退款结果
            report($e);
        }
    }
}
